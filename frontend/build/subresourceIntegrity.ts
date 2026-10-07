import { createHash } from 'node:crypto'
import { readFileSync, writeFileSync } from 'node:fs'
import { join } from 'node:path'
import type { Plugin } from 'vite'

/**
 * Subresource Integrity (SRI) no index.html do build: cada <script src> e <link href> (stylesheet/modulepreload)
 * apontando para um arquivo do próprio bundle ganha integrity="sha384-..." e crossorigin. Se o arquivo for
 * alterado no caminho (cache/CDN/proxy comprometido), o navegador recusa o carregamento.
 *
 * Telas carregadas sob demanda (import()) não têm SRI: o navegador não suporta integrity em import dinâmico.
 */
export function subresourceIntegrity(): Plugin {
  return {
    name: 'sysfinance:subresource-integrity',
    apply: 'build',
    enforce: 'post',
    // Depois de gravar: o hash precisa ser dos bytes finais (o Vite ainda reescreve chunks no generateBundle).
    writeBundle(options, bundle) {
      const outDir = options.dir ?? 'dist'
      const htmlPath = join(outDir, 'index.html')
      if (!bundle['index.html']) return

      const hashes = new Map<string, string>()
      for (const fileName of Object.keys(bundle)) {
        if (!/\.(js|css)$/.test(fileName)) continue
        const content = readFileSync(join(outDir, fileName))
        hashes.set('/' + fileName, 'sha384-' + createHash('sha384').update(content).digest('base64'))
      }
      writeFileSync(htmlPath, addIntegrity(readFileSync(htmlPath, 'utf8'), hashes))
    },
  }
}

/** Exportada para teste: acrescenta integrity/crossorigin às tags que apontam para arquivos conhecidos. */
export function addIntegrity(html: string, hashes: Map<string, string>): string {
  return html.replace(/<(script|link)\b([^>]*?)\s(src|href)="([^"]+)"([^>]*)>/g, (tag, name, before, attr, url, after) => {
    const integrity = hashes.get(url.split(/[?#]/)[0])
    if (!integrity || /\sintegrity=/.test(tag)) return tag
    if (name === 'link' && !/rel="(stylesheet|modulepreload)"/.test(tag)) return tag
    const crossorigin = /\scrossorigin\b/.test(tag) ? '' : ' crossorigin'
    return `<${name}${before} ${attr}="${url}"${after.replace(/\s*\/?$/, '')} integrity="${integrity}"${crossorigin}>`
  })
}
