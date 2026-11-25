<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

function contrastRatio(string $first, string $second): float
{
    $luminance = function (string $hex): float {
        $channels = array_map(function (string $channel): float {
            $value = hexdec($channel) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;
    };
    $values = [$luminance($first), $luminance($second)];
    sort($values);

    return ($values[1] + 0.05) / ($values[0] + 0.05);
}

try {
    foreach ($files as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        if (str_starts_with($relative, '.git/') || str_starts_with($relative, 'var/')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        $extension = strtolower($file->getExtension());

        if ($extension === 'php') {
            foreach (token_get_all($contents) as $token) {
                if (is_array($token) && (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) || $token[0] === T_INLINE_HTML && str_contains($token[1], '<!--'))) {
                    throw new RuntimeException('Commentaire interdit : ' . $relative);
                }
            }
        } elseif (in_array($extension, ['sql', 'css', 'js', 'html', 'svg'], true)) {
            $withoutStrings = preg_replace('/([\x22\x27])(?:\\\\.|(?!\1).)*\1/s', '', $contents);

            if (str_contains($withoutStrings, '/*') || str_contains($withoutStrings, '<!--') || ($extension === 'js' && preg_match('~(^|\s)//~m', $withoutStrings)) || ($extension === 'sql' && preg_match('/(^|\s)(--|#)/m', $withoutStrings))) {
                throw new RuntimeException('Commentaire interdit : ' . $relative);
            }
        }
    }

    $css = file_get_contents($root . '/assets/style.css');
    preg_match_all('/#[a-fA-F0-9]{6}\b/', $css, $colors);

    if (array_diff(array_unique(array_map('strtoupper', $colors[0])), ['#F8FAFC', '#E2E8F0', '#3B82F6', '#1E40AF', '#0F172A'])) {
        throw new RuntimeException('Couleur de marque non autorisee.');
    }

    foreach ([['#0F172A', '#F8FAFC', 4.5], ['#0F172A', '#3B82F6', 4.5], ['#1E40AF', '#F8FAFC', 4.5], ['#F8FAFC', '#1E40AF', 4.5], ['#888D97', '#F8FAFC', 3.0]] as [$foreground, $background, $minimum]) {
        if (contrastRatio($foreground, $background) < $minimum) {
            throw new RuntimeException('Contraste de base insuffisant.');
        }
    }

    echo 'Sources sans commentaires interdits, palette et contrastes de base conformes.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
