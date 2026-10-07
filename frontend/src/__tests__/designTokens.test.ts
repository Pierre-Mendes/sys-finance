/// <reference types="node" />
import { describe, it, expect } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

// Lidos do disco: no Vitest o import de .css (mesmo ?raw) volta vazio
const read = (rel: string) => readFileSync(resolve(__dirname, rel), 'utf8')
const tokensRaw = read('../../../design-system/tokens.json')
const themeCss = read('../style.css')
const darkCss = read('../styles/dark.css')

// design-system/tokens.json é a fonte da verdade; o CSS do app precisa usar os mesmos valores
const tokens = JSON.parse(tokensRaw)
const declared = (css: string, name: string) => css.match(new RegExp(`--${name}:\\s*([^;]+);`))?.[1].trim().toUpperCase()

describe('tokens do design system', () => {
  it.each(['brand', 'capi', 'ink'])('escala %s igual em style.css', (scale) => {
    for (const [step, hex] of Object.entries<string>(tokens.color[scale])) {
      expect(declared(themeCss, `color-${scale}-${step}`), `${scale}-${step}`).toBe(hex.toUpperCase())
    }
  })

  it('cores semânticas iguais em style.css', () => {
    for (const [name, hex] of Object.entries<string>(tokens.color.semantic)) {
      expect(declared(themeCss, `color-${name}`), name).toBe(hex.toUpperCase())
    }
  })

  it('paleta escura igual em dark.css', () => {
    for (const [name, hex] of Object.entries<string>(tokens.color.dark)) {
      expect(declared(darkCss, `dk-${name}`), name).toBe(hex.toUpperCase())
    }
  })

  it('fontes e curvas de movimento iguais em style.css', () => {
    expect(declared(themeCss, 'font-sans')).toContain(tokens.font.sans.toUpperCase())
    expect(declared(themeCss, 'font-display')).toContain(tokens.font.display.toUpperCase())
    expect(declared(themeCss, 'ease-out')).toBe(tokens.motion['ease-out'].toUpperCase())
    expect(declared(themeCss, 'ease-spring')).toBe(tokens.motion['ease-spring'].toUpperCase())
  })
})
