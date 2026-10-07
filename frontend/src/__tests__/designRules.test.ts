/// <reference types="node" />
import { describe, it, expect } from 'vitest'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { join, relative, resolve } from 'node:path'

// Regras do design system conferidas no código-fonte (docs/conventions/design-system.md)
const SRC = resolve(__dirname, '..')
const files = (dir: string): string[] =>
  readdirSync(dir).flatMap((name) => {
    const path = join(dir, name)
    if (statSync(path).isDirectory()) return name === '__tests__' ? [] : files(path)
    return /\.(vue|ts)$/.test(name) ? [path] : []
  })
const sources = files(SRC).map((path) => ({ path: relative(SRC, path), text: readFileSync(path, 'utf8') }))

// Animações nativas do Tailwind + as definidas no @theme de style.css
const TAILWIND = ['spin', 'ping', 'pulse', 'bounce', 'none']
const THEME = [...readFileSync(join(SRC, 'style.css'), 'utf8').matchAll(/--animate-([a-z0-9-]+):/g)].map((m) => m[1])

describe('regras do design system', () => {
  it('toda classe animate-* existe para o arquivo que a usa', () => {
    const missing: string[] = []
    for (const { path, text } of sources) {
      const template = text.split('<style')[0]
      // Definida num <style> do próprio componente vale só para ele (scoped não alcança outros arquivos)
      const local = [...text.matchAll(/\.animate-([a-z0-9-]+)\s*\{/g)].map((m) => m[1])
      for (const m of template.matchAll(/(?:^|[\s"'`:])animate-([a-z0-9-]+)/g)) {
        const name = m[1]
        if (![...TAILWIND, ...THEME, ...local].includes(name)) missing.push(`${path}: animate-${name}`)
      }
    }
    expect(missing).toEqual([])
  })

  it('sem degradês decorativos (use cor sólida de token)', () => {
    const found = sources
      .filter(({ text }) => /\bbg-(gradient|linear|radial|conic)-/.test(text))
      .map(({ path }) => path)
    expect(found).toEqual([])
  })
})
