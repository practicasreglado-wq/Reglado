<?php
$path = 'c:/xampp/htdocs/Reglado/Inmobiliaria_Reglados/backend/processing/PropertyProcessor.php';
$content = file_get_contents($path);

// The text we want to remove/replace
$old = <<<'PHP'
        $defaultOwnerUserId = (int) (getenv('DEFAULT_OWNER_USER_ID') ?: 1);
        if ($defaultOwnerUserId <= 0) {
            $defaultOwnerUserId = 1;
        }

        if ($normalizedOwnerEmail === null) {
            $resolvedOwnerUserId = $defaultOwnerUserId;
PHP;

$new = <<<'PHP'
        if ($normalizedOwnerEmail === null) {
            $resolvedOwnerUserId = $this->createdByUserId;
PHP;

$content = str_replace(str_replace("\n", "\r\n", $old), str_replace("\n", "\r\n", $new), $content);

file_put_contents($path, $content);
echo "Patched\n";
