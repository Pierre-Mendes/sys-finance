import { describe, it, expect } from 'vitest'
import { addIntegrity } from '../subresourceIntegrity'

const hashes = new Map([
  ['/assets/index-a.js', 'sha384-JS'],
  ['/assets/index-b.css', 'sha384-CSS'],
  ['/assets/vendor-c.js', 'sha384-PRE'],
])

describe('addIntegrity', () => {
  it('adiciona integrity e crossorigin aos arquivos do bundle', () => {
    const html = [
      '<script type="module" crossorigin src="/assets/index-a.js"></script>',
      '<link rel="stylesheet" crossorigin href="/assets/index-b.css">',
      '<link rel="modulepreload" href="/assets/vendor-c.js">',
    ].join('\n')

    const out = addIntegrity(html, hashes)

    expect(out).toContain('<script type="module" crossorigin src="/assets/index-a.js" integrity="sha384-JS"></script>')
    expect(out).toContain('<link rel="stylesheet" crossorigin href="/assets/index-b.css" integrity="sha384-CSS">')
    expect(out).toContain('<link rel="modulepreload" href="/assets/vendor-c.js" integrity="sha384-PRE" crossorigin>')
  })

  it('não mexe em ícones, manifest, arquivos de fora do bundle nem em quem já tem integrity', () => {
    const html = [
      '<link rel="icon" href="/assets/index-b.css" />',
      '<link rel="manifest" href="/manifest.webmanifest" />',
      '<script src="/sw-register.js"></script>',
      '<script src="/assets/index-a.js" integrity="sha384-OUTRO"></script>',
    ].join('\n')

    expect(addIntegrity(html, hashes)).toBe(html)
  })
})
