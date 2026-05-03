param(
    [Parameter(Position = 0)]
    [string]$Command,

    [Parameter(Position = 1, ValueFromRemainingArguments = $true)]
    [string[]]$CommandArgs
)

$ErrorActionPreference = 'Stop'

function Import-DotEnv {
    param([string]$Path = '.env')

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    Get-Content -LiteralPath $Path | ForEach-Object {
        $line = $_.Trim()

        if ($line -eq '' -or $line.StartsWith('#')) {
            return
        }

        $separatorIndex = $line.IndexOf('=')
        if ($separatorIndex -lt 1) {
            return
        }

        $name = $line.Substring(0, $separatorIndex).Trim()
        $value = $line.Substring($separatorIndex + 1).Trim()

        if (
            ($value.StartsWith('"') -and $value.EndsWith('"')) -or
            ($value.StartsWith("'") -and $value.EndsWith("'"))
        ) {
            $value = $value.Substring(1, $value.Length - 2)
        }

        [Environment]::SetEnvironmentVariable($name, $value, 'Process')
    }
}

function Assert-NativeSuccess {
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

function Invoke-DockerCompose {
    $dockerCompose = Get-Command docker-compose -ErrorAction SilentlyContinue

    if ($dockerCompose) {
        docker-compose @args
        Assert-NativeSuccess
        return
    }

    docker compose @args
    Assert-NativeSuccess
}

function Show-Usage {
    @'
Uso:
  .\run.ps1 <comando> [argumentos]

Comandos:
  ps
  up [argumentos]
  down [argumentos]
  composer [argumentos]
  nginx:check
  nginx:status
  nginx:reload
  php:console
  test [caminho]
  codecept [argumentos]
  test:browser [caminho]
  phpcs [caminho]
  phpcbf [caminho]
  phpstan [caminho]
  git:clean:branchs
  db:console
  db:reset
  db:populate
'@
}

Import-DotEnv

if ([string]::IsNullOrWhiteSpace($Command)) {
    Show-Usage
    exit 1
}

$elapsed = [System.Diagnostics.Stopwatch]::StartNew()

try {
    switch ($Command) {
        'ps' {
            Invoke-DockerCompose ps
        }

        'up' {
            Invoke-DockerCompose up @CommandArgs
        }

        'down' {
            Invoke-DockerCompose down @CommandArgs
        }

        'composer' {
            docker run --rm --interactive `
                -e COMPOSER_CACHE_DIR='/app/.cache/composer' `
                -v "${PWD}:/app" `
                -w /app composer:2.7.2 composer @CommandArgs
            Assert-NativeSuccess
        }

        'nginx:check' {
            Invoke-DockerCompose exec web nginx -t
        }

        'nginx:status' {
            Invoke-DockerCompose exec web service nginx status
        }

        'nginx:reload' {
            Invoke-DockerCompose exec web nginx -s reload
        }

        'php:console' {
            Invoke-DockerCompose exec php php -a
        }

        'test' {
            $testPath = if ($CommandArgs.Count -gt 0) { $CommandArgs } else { @('tests') }
            Invoke-DockerCompose run --rm php_test ./vendor/bin/phpunit `
                --color=always `
                --testdox `
                @testPath
        }

        'codecept' {
            Invoke-DockerCompose run --rm php_test ./vendor/bin/codecept @CommandArgs
        }

        'test:browser' {
            $testPath = if ($CommandArgs.Count -gt 0) { $CommandArgs } else { @('tests/Acceptance') }
            Invoke-DockerCompose run --rm php_test ./vendor/bin/codecept run acceptance @testPath
        }

        'phpcs' {
            $testPath = if ($CommandArgs.Count -gt 0) { $CommandArgs } else { @('.') }
            Invoke-DockerCompose run --rm php ./vendor/bin/phpcs @testPath
        }

        'phpcbf' {
            $testPath = if ($CommandArgs.Count -gt 0) { $CommandArgs } else { @('.') }
            Invoke-DockerCompose run --rm php ./vendor/bin/phpcbf @testPath
        }

        'phpstan' {
            Invoke-DockerCompose run --rm php ./vendor/bin/codecept build
            Invoke-DockerCompose run --rm php ./vendor/bin/phpstan analyse --memory-limit 1G @CommandArgs
        }

        'git:clean:branchs' {
            git branch |
                Where-Object { $_ -notmatch '^\*|master|main|develop|production' } |
                ForEach-Object { git branch -D $_.Trim() }
        }

        'db:console' {
            Invoke-DockerCompose exec db sh -c 'MYSQL_PWD="$DB_PASSWORD" mysql -u "$DB_USERNAME" "$DB_DATABASE" --default-character-set utf8mb4'
        }

        'db:reset' {
            Invoke-DockerCompose cp database/schema.sql db:/tmp/
            Invoke-DockerCompose exec db sh -c 'MYSQL_PWD="$DB_PASSWORD" mysql -u "$DB_USERNAME" "$DB_DATABASE" --default-character-set utf8mb4 < /tmp/schema.sql'
        }

        'db:populate' {
            Invoke-DockerCompose exec php php database/Populate/populate.php
        }

        default {
            [Console]::Error.WriteLine("Comando desconhecido: $Command")
            Show-Usage
            exit 1
        }
    }
}
finally {
    $elapsed.Stop()
    Write-Host ''
    Write-Host ('Task completed in {0:hh\:mm\:ss\.fff}' -f $elapsed.Elapsed)
}
