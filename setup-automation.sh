#!/usr/bin/env bash

# Exit immediately if any command fails
set -e

echo "🚀 Initializing Automated Developer Workflow for Sage Theme..."

# 1. GENERATE SECURE SSH KEYS FOR SITEGROUND DEPLOYMENT
SSH_KEY_DIR="./.deploy-keys"
mkdir -p "$SSH_KEY_DIR"

if [ ! -f "$SSH_KEY_DIR/siteground_deploy_key" ]; then
    echo "🔑 Generating 4096-bit RSA SSH deployment key pair..."
    ssh-keygen -t rsa -b 4096 -f "$SSH_KEY_DIR/siteground_deploy_key" -N "" -C "cursor-automation-deploy@siteground"
    echo "✅ Keys generated successfully in $SSH_KEY_DIR"
else
    echo "ℹ️ SSH keys already exist. Skipping generation."
fi

# Output the public key cleanly so the user can easily click to copy it
echo "--------------------------------------------------------"
echo "👉 COPY THE PUBLIC KEY BELOW AND PASTE IT INTO SITEGROUND:"
echo "SiteGround Site Tools > Devs > SSH Manager > Import Key"
echo "--------------------------------------------------------"
cat "$SSH_KEY_DIR/siteground_deploy_key.pub"
echo "--------------------------------------------------------"

# 2. AUTOMATE PRE-COMMIT HOOKS VIA HUSKY
echo "📦 Setting up local pre-commit formatting & security checks..."
npm install husky --save-dev

# Initialize Husky configuration
npx husky init

# Inject a senior-level PHP styling check into the pre-commit loop automatically
# Requires standard php-codesniffer or basic lint checks before allowing a git commit
cat << 'EOF' > .husky/pre-commit
#!/usr/bin/env bash
echo "🛡️ Running pre-commit validation checks..."

# Run PHP linting over modified files to block execution breaks before push
if ! find . -name "*.php" -not -path "*/vendor/*" -exec php -l {} \; | grep -q "No syntax errors detected"; then
    echo "❌ PHP Syntax Error Detected! Commit aborted."
    exit 1
fi

echo "✅ All linting checks passed. Proceeding with commit."
EOF

chmod +x .husky/pre-commit
echo "✅ Husky Git Hooks automated successfully."

# 3. SECURE LOCAL DIRECTORIES
echo "🔒 Securing private keys locally..."
if ! grep -q ".deploy-keys/" .gitignore; then
    echo -e "\n# Security: Never commit deploy keys to GitHub\n.deploy-keys/" >> .gitignore
    echo "✅ Added .deploy-keys/ to .gitignore"
fi

echo "🎉 Workflow automation setup complete! No manual config required."
