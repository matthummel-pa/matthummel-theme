const fs = require('fs')
const path = require('path')

const blockPath = path.join(__dirname, '..', 'build', 'signup', 'block.json')
const block = JSON.parse(fs.readFileSync(blockPath, 'utf8'))

if (block.style === 'file:./style.scss') {
  block.style = 'file:./style-index.css'
  fs.writeFileSync(blockPath, JSON.stringify(block, null, 2) + '\n')
}
