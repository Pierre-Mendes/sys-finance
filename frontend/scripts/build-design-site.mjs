// Gera uma versão estática (HTML puro) do canvas de design para abrir sem o editor do claude.ai.
// Entrada: design-system/canvas/*.dc.html + canvas.json. Saída: design-system/site/.
// Uso: npm run design:build-site
//
// Cada quadro é renderizado uma vez com o estado inicial: {{campos}}, <sc-for>, <sc-if> e <dc-import>
// viram HTML comum. Animações CSS e SVG continuam; cliques (toggle, "Pagar", "Reproduzir") não.
import { readFileSync, writeFileSync, mkdirSync, readdirSync, rmSync, copyFileSync, existsSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { JSDOM } from 'jsdom'

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '../..')
const CANVAS = join(ROOT, 'design-system/canvas')
// DESIGN_SITE_OUT: usado pelo teste para gerar numa pasta temporária e comparar com o que está versionado
const OUT = process.env.DESIGN_SITE_OUT ? resolve(process.env.DESIGN_SITE_OUT) : join(ROOT, 'design-system/site')
const CANVAS_URL = 'https://claude.ai/artifact/EpKQjikiEQyb6b7XGnpEZu'

const index = JSON.parse(readFileSync(join(CANVAS, 'canvas.json'), 'utf8'))
// Exportação do canvas interativo (claude.ai → Share → baixar HTML): um arquivo autocontido, copiado como está
const INTERACTIVE_SRC = join(CANVAS, 'canvas-interativo.html')
const INTERACTIVE_PAGE = 'interativo.html'
const hasInteractive = existsSync(INTERACTIVE_SRC)

// ---------- avaliação de {{campos}} ----------

const HOLE = /\{\{\s*([^}]+?)\s*\}\}/g
const WHOLE = /^\s*\{\{\s*([^}]+?)\s*\}\}\s*$/

function lookup(expr, scope) {
  if (expr === 'true') return true
  if (expr === 'false') return false
  if (expr === 'null') return null
  if (/^-?\d+(\.\d+)?$/.test(expr)) return Number(expr)
  if (/^(['"]).*\1$/.test(expr)) return expr.slice(1, -1)
  return expr.split('.').reduce((obj, key) => (obj == null ? undefined : obj[key]), scope)
}

const interpolate = (text, scope) =>
  text.replace(HOLE, (_, expr) => {
    const v = lookup(expr, scope)
    return v == null || typeof v === 'function' ? '' : String(v)
  })

const rawOrText = (value, scope) => {
  const m = value.match(WHOLE)
  return m ? lookup(m[1], scope) : interpolate(value, scope)
}

const camel = (name) => name.replace(/-([a-z])/g, (_, c) => c.toUpperCase())

// ---------- lógica do componente (class Component extends DCLogic) ----------

class DCLogic {
  constructor(props) {
    this.props = props || {}
    this.state = {}
  }
  setState(update) {
    Object.assign(this.state, typeof update === 'function' ? update(this.state) : update)
  }
  forceUpdate() {}
}

function runLogic(source, props) {
  const fakeWindow = { matchMedia: () => ({ matches: false }) }
  // rAF roda na hora com um tempo "no futuro": contadores (ex.: saldo do quadro Movimento) param no valor final
  const factory = new Function(
    'DCLogic', 'window', 'requestAnimationFrame', 'cancelAnimationFrame', 'performance', 'setTimeout', 'clearTimeout',
    `${source}\nreturn Component;`,
  )
  const Component = factory(DCLogic, fakeWindow, (cb) => cb(1e12), () => {}, { now: () => 0 }, () => 0, () => {})
  const instance = new Component(props)
  instance.componentDidMount?.()
  return instance.renderVals()
}

// ---------- renderização dos quadros ----------

const cache = new Map()
function loadBoard(name) {
  if (!cache.has(name)) {
    const dom = new JSDOM(readFileSync(join(CANVAS, name), 'utf8'))
    const doc = dom.window.document
    const script = doc.querySelector('script[data-dc-script]')
    cache.set(name, {
      title: doc.querySelector('title')?.textContent ?? name,
      lang: doc.documentElement.getAttribute('lang') || 'pt-BR',
      helmet: doc.querySelector('x-dc > helmet'),
      root: doc.querySelector('x-dc'),
      logic: script?.textContent ?? 'class Component extends DCLogic { renderVals() { return {} } }',
    })
  }
  return cache.get(name)
}

function renderChildren(parent, scope, out, ctx) {
  for (const child of [...parent.childNodes]) renderNode(child, scope, out, ctx)
}

function renderNode(node, scope, out, ctx) {
  const doc = out.ownerDocument
  if (node.nodeType === 3) {
    out.appendChild(doc.createTextNode(interpolate(node.textContent, scope)))
    return
  }
  if (node.nodeType !== 1) return
  const tag = node.tagName.toLowerCase()
  if (tag === 'helmet') return

  if (tag === 'sc-for') {
    const list = rawOrText(node.getAttribute('list') || '', scope) || []
    const as = node.getAttribute('as') || 'item'
    list.forEach((item, i) => renderChildren(node, { ...scope, [as]: item, $index: i }, out, ctx))
    return
  }
  if (tag === 'sc-if') {
    if (rawOrText(node.getAttribute('value') || '', scope)) renderChildren(node, scope, out, ctx)
    return
  }
  if (tag === 'dc-import') {
    const props = {}
    for (const attr of node.attributes) {
      if (attr.name === 'name' || attr.name.startsWith('hint-')) continue
      props[camel(attr.name)] = rawOrText(attr.value, scope)
    }
    renderBoardInto(`${node.getAttribute('name')}.dc.html`, props, out, ctx)
    return
  }

  const el = doc.createElementNS(node.namespaceURI, node.tagName)
  for (const attr of node.attributes) {
    if (/^on/i.test(attr.name)) continue // handlers não existem na versão estática
    let value = rawOrText(attr.value, scope)
    // Links entre quadros apontam para a página estática equivalente
    if (attr.name === 'href' && typeof value === 'string') value = value.replace(/\.dc\.html(#|$)/, '.html$1')
    if (value === false || value == null) continue
    el.setAttributeNS(attr.namespaceURI, attr.name, value === true ? '' : String(value))
  }
  out.appendChild(el)
  renderChildren(node, scope, el, ctx)
}

function renderBoardInto(name, props, out, ctx) {
  const board = loadBoard(name)
  if (board.helmet) ctx.helmets.add(name)
  renderChildren(board.root, runLogic(board.logic, props), out, ctx)
}

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')

function boardPage(file, entry) {
  const board = loadBoard(file)
  const dom = new JSDOM('<!doctype html><html><head></head><body></body></html>')
  const doc = dom.window.document
  const holder = doc.createElement('div')
  const ctx = { helmets: new Set() }
  renderBoardInto(file, {}, holder, ctx)
  const helmet = [...ctx.helmets].map((n) => loadBoard(n).helmet.innerHTML.trim()).join('\n')
  const isPage = entry.expand === 'fill'
  return `<!doctype html>
<html lang="${esc(board.lang)}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(entry.title || board.title)} · Design system sysfinance</title>
${helmet}
<style>
.ds-bar{position:sticky;top:0;z-index:9999;display:flex;align-items:center;gap:12px;padding:10px 16px;background:#0B1324;color:#C5CCD6;font:600 14px/1.4 Manrope,system-ui,sans-serif}
.ds-bar a{color:#fff;font-weight:700;text-decoration:none;padding:6px 10px;border-radius:8px}
.ds-bar a:hover{background:#26324A}
.ds-bar a:focus-visible{outline:3px solid #6F8BF0;outline-offset:2px}
${isPage ? '' : `.ds-board{width:${entry.w}px;min-height:${entry.h}px}`}
</style>
</head>
<body>
<nav class="ds-bar" aria-label="Design system"><a href="index.html">← Todos os quadros</a><span>${esc(entry.title || board.title)} · versão estática</span></nav>
<div class="ds-board">
${holder.innerHTML}
</div>
</body>
</html>
`
}

function indexPage(boards) {
  const cards = boards
    .map(({ file, entry, page }) => {
      const scale = 360 / entry.w
      return `<li class="card">
  <a href="${page}">
    <span class="thumb" style="height:${Math.round(Math.min(entry.h, 1100) * scale)}px">
      <iframe src="${page}" title="Prévia: ${esc(entry.title || file)}" loading="lazy" tabindex="-1" aria-hidden="true"
        style="width:${entry.w}px;height:${Math.min(entry.h, 1100)}px;transform:scale(${scale.toFixed(4)})"></iframe>
    </span>
    <span class="label">${esc(entry.title || file)}</span>
  </a>
</li>`
    })
    .join('\n')
  return `<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Design system sysfinance</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Manrope:wght@400;600;700&display=swap">
<style>
:root{--bg:#F6F7F9;--surface:#FFFFFF;--text:#0B1324;--muted:#5B6B85;--brand:#2346D8;--line:#EEF1F5}
@media (prefers-color-scheme: dark){:root{--bg:#0F1930;--surface:#17223A;--text:#EEF1F5;--muted:#9AA6BA;--brand:#8AA1F2;--line:#2A3754}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Manrope,system-ui,sans-serif}
main{max-width:1200px;margin:0 auto;padding:48px 16px 64px}
header{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:12px}
h1{font-family:'Bricolage Grotesque',Manrope,sans-serif;font-weight:800;font-size:clamp(32px,5vw,48px);letter-spacing:-.02em;margin:0}
p{color:var(--muted);line-height:1.6;max-width:760px;margin:0 0 8px}
a{color:var(--brand)}
ul{list-style:none;padding:0;margin:32px 0 0;display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,360px),1fr));gap:24px}
.card a{display:block;text-decoration:none;color:inherit;background:var(--surface);border:1px solid var(--line);border-radius:20px;overflow:hidden;transition:transform 200ms cubic-bezier(.2,.8,.2,1),box-shadow 200ms}
.card a:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(11,19,36,.12)}
.card a:focus-visible{outline:3px solid #6F8BF0;outline-offset:2px}
.thumb{display:block;width:100%;overflow:hidden;position:relative;background:var(--line);pointer-events:none}
.thumb iframe{border:0;transform-origin:0 0;position:absolute;left:0;top:0;background:#fff}
.label{display:block;padding:14px 18px;font-weight:700}
.cta{display:flex;flex-direction:column;gap:6px;margin:24px 0 16px;padding:20px 24px;border-radius:20px;background:#2346D8;color:#fff;text-decoration:none;max-width:760px;transition:background-color 200ms}
.cta:hover{background:#1A34A6}
.cta:focus-visible{outline:3px solid #6F8BF0;outline-offset:2px}
.cta-title{font-family:'Bricolage Grotesque',Manrope,sans-serif;font-weight:800;font-size:22px}
.cta-text{color:#D3DCFA;line-height:1.5}
@media (prefers-reduced-motion: reduce){.card a{transition:none}}
</style>
</head>
<body>
<main>
<header>
<svg width="48" height="48" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="18" fill="#2346D8"/><path d="M15 42 L26 31 L34 37 L47 22" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="48" cy="20" r="7" fill="#F2A541"/></svg>
<h1>Design system sysfinance</h1>
</header>
${hasInteractive ? `<a class="cta" href="${INTERACTIVE_PAGE}">
  <span class="cta-title">Abrir a versão interativa →</span>
  <span class="cta-text">O canvas completo: navegue entre os quadros com zoom, clique nos componentes, alterne o toggle, pague contas no painel e reproduza as animações.</span>
</a>` : ''}
<p>Abaixo, cada quadro também como página estática (abre rápido e funciona sem JavaScript pesado): as animações rodam,
mas os cliques ${hasInteractive ? 'ficam na versão interativa acima' : `só funcionam no <a href="${CANVAS_URL}">canvas interativo no claude.ai</a>`}.
O original editável fica no <a href="${CANVAS_URL}">claude.ai</a> (privado; compartilhe pelo menu Share).</p>
<p>Para regenerar depois de mudar o canvas: <code>cd frontend &amp;&amp; npm run design:build-site</code>.</p>
<ul>
${cards}
</ul>
</main>
</body>
</html>
`
}

// ---------- saída ----------

rmSync(OUT, { recursive: true, force: true })
mkdirSync(OUT, { recursive: true })
const boards = index.order
  .filter((file) => file !== 'Capi.dc.html' || index.order.length === 1) // a Capi aparece dentro dos outros quadros
  .map((file) => ({ file, entry: index.boards[file], page: file.replace(/\.dc\.html$/, '.html') }))

for (const b of boards) writeFileSync(join(OUT, b.page), boardPage(b.file, b.entry))
if (hasInteractive) copyFileSync(INTERACTIVE_SRC, join(OUT, INTERACTIVE_PAGE))
writeFileSync(join(OUT, 'index.html'), indexPage(boards))
console.log(`${OUT}: ${readdirSync(OUT).length} arquivos (${boards.map((b) => b.page).join(', ')}, index.html)`)
