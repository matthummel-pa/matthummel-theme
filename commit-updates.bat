@echo off
echo Copying updated files to theme...
copy /Y "C:\ClaudeCowork\Projects\SageRootsDev\app\quick-setup.php" "C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme\app\quick-setup.php"
copy /Y "C:\ClaudeCowork\Projects\SageRootsDev\app\social-links.php" "C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme\app\social-links.php"
copy /Y "C:\ClaudeCowork\Projects\SageRootsDev\app\admin-settings.php" "C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme\app\admin-settings.php"
copy /Y "C:\ClaudeCowork\Projects\SageRootsDev\app\header-elements.php" "C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme\app\header-elements.php"
copy /Y "C:\ClaudeCowork\Projects\SageRootsDev\app\customizer.php" "C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme\app\customizer.php"

cd /d C:\Users\mhumm\wordpress\app\public\wp-content\themes\matthummel-theme

echo Running PHP syntax checks...
php -l app\quick-setup.php
php -l app\social-links.php
php -l app\admin-settings.php
php -l app\header-elements.php
php -l app\customizer.php

echo Committing...
git add app\quick-setup.php app\social-links.php app\admin-settings.php app\header-elements.php app\customizer.php CHANGELOG.md
git commit -m "feat: Google Fonts library, Style Kit fix, social Quick Setup, nav postMessage, content import"
git push origin main
echo.
echo Done. Check localhost:8888 and localhost:8888/wp-admin/customize.php
pause
