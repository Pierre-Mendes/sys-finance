// Confere o SRI do build: todo <script src> e <link rel=stylesheet|modulepreload> de /assets no dist/index.html
// precisa ter integrity igual ao sha384 do arquivo gravado. Roda no CI depois do `npm run build`.
import { createHash } from 'node:crypto'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'

const dist = process.argv[2] ?? 'dist'
const html = readFileSync(join(dist, 'index.html'), 'utf8')
const tags = [...html.matchAll(/<(script|link)\b[^>]*>/g)].map((m) => m[0])
let checked = 0
const problems = []

for (const tag of tags) {
  const url = tag.match(/\s(?:src|href)="(\/assets\/[^"]+)"/)?.[1]
  if (!url) continue
  if (tag.startsWith('<link') && !/rel="(stylesheet|modulepreload)"/.test(tag)) continue
  checked++
  const expected = 'sha384-' + createHash('sha384').update(readFileSync(join(dist, url))).digest('base64')
  const actual = tag.match(/\sintegrity="([^"]+)"/)?.[1]
  if (actual !== expected) problems.push(`${url}: integrity ${actual ? 'diferente do arquivo' : 'ausente'}`)
}

if (checked === 0) problems.push('nenhum script/estilo de /assets encontrado no index.html')
if (problems.length) {
  console.error('SRI inválido:\n- ' + problems.join('\n- '))
  process.exit(1)
}
console.log(`SRI ok: ${checked} arquivo(s) com integrity correto.`)
