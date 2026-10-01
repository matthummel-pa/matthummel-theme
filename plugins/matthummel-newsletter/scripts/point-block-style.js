const fs = require('fs')
const path = require('path')

const buildDir = path.join(__dirname, '..', 'build', 'signup')
const blockPath = path.join(buildDir, 'block.json')
const block = JSON.parse(fs.readFileSync(blockPath, 'utf8'))

if (block.style === 'file:./style.scss') {
  block.style = 'file:./style-index.css'
  fs.writeFileSync(blockPath, JSON.stringify(block, null, 2) + '\n')
}

for (const name of fs.readdirSync(buildDir)) {
  if (name.endsWith('.asset.php')) formatAssetPhp(path.join(buildDir, name))
}

function formatAssetPhp (filePath) {
  const php = fs.readFileSync(filePath, 'utf8')
  const version = php.match(/'version'\s*=>\s*'([^']+)'/)
  if (!version) return

  const deps = []
  const depBlock = php.match(/'dependencies'\s*=>\s*array\s*\(([\s\S]*?)\)/)
  if (depBlock) {
    for (const match of depBlock[1].matchAll(/'([^']+)'/g)) deps.push(match[1])
  }

  const depLines = deps.length === 0
    ? '[]'
    : '[\n' + deps.map((dep) => `        '${dep}',`).join('\n') + '\n    ]'
  const out = `<?php\n\nreturn [\n    'dependencies' => ${depLines},\n    'version' => '${version[1]}',\n];\n`
  fs.writeFileSync(filePath, out)
}
