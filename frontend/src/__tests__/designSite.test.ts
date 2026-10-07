/// <reference types="node" />
import { describe, it, expect } from 'vitest'
import { execFileSync } from 'node:child_process'
import { mkdtempSync, readdirSync, readFileSync, rmSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, resolve } from 'node:path'

// design-system/site/ é gerado de design-system/canvas/ por scripts/build-design-site.mjs.
// Se o canvas mudar e ninguém regenerar o site, este teste falha (rode: npm run design:build-site).
const FRONTEND = resolve(__dirname, '../..')
const SITE = resolve(FRONTEND, '../design-system/site')

describe('site estático do design system', () => {
  it('está atualizado com o canvas', () => {
    const out = mkdtempSync(join(tmpdir(), 'design-site-'))
    try {
      execFileSync(process.execPath, [join(FRONTEND, 'scripts/build-design-site.mjs')], {
        env: { ...process.env, DESIGN_SITE_OUT: out },
        stdio: 'pipe',
      })
      const generated = readdirSync(out).sort()
      expect(readdirSync(SITE).sort()).toEqual(generated)
      for (const file of generated) {
        expect(readFileSync(join(SITE, file), 'utf8'), file).toBe(readFileSync(join(out, file), 'utf8'))
      }
    } finally {
      rmSync(out, { recursive: true, force: true })
    }
  }, 30_000)

  it('não deixa {{campos}} sem preencher', () => {
    for (const file of readdirSync(SITE)) {
      expect(readFileSync(join(SITE, file), 'utf8'), file).not.toMatch(/\{\{[^}]*\}\}/)
    }
  })
})
