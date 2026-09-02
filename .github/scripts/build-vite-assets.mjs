import { spawnSync } from 'node:child_process'
import {
  copyFileSync,
  cpSync,
  existsSync,
  mkdirSync,
  readdirSync,
  renameSync,
  rmSync,
} from 'node:fs'
import { resolve } from 'node:path'

const liveDir = resolve('public/build')
const nextDir = resolve('public/build-next')
const manifestName = 'manifest.json'

rmSync(nextDir, { recursive: true, force: true })

const vite = spawnSync(
  process.execPath,
  [resolve('node_modules/vite/bin/vite.js'), 'build', '--outDir', nextDir, '--emptyOutDir'],
  { stdio: 'inherit' },
)

if (vite.status !== 0) {
  rmSync(nextDir, { recursive: true, force: true })
  process.exit(vite.status ?? 1)
}

const nextManifest = resolve(nextDir, manifestName)
if (! existsSync(nextManifest)) {
  console.error(`Vite did not create ${nextManifest}`)
  rmSync(nextDir, { recursive: true, force: true })
  process.exit(1)
}

mkdirSync(liveDir, { recursive: true })

for (const entry of readdirSync(nextDir)) {
  if (entry === manifestName) continue
  cpSync(resolve(nextDir, entry), resolve(liveDir, entry), {
    recursive: true,
    force: true,
  })
}

const pendingManifest = resolve(liveDir, `${manifestName}.next`)
copyFileSync(nextManifest, pendingManifest)
renameSync(pendingManifest, resolve(liveDir, manifestName))
rmSync(nextDir, { recursive: true, force: true })
