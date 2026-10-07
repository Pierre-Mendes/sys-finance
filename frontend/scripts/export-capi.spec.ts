/// <reference types="node" />
// Exporta a Capi (CapiMascot.vue) como SVG estático para design-system/capi/.
// Uso: npm run design:export-capi  (fora disso o teste é pulado e não escreve nada)
import { it } from 'vitest'
import { createSSRApp, h } from 'vue'
import { renderToString } from 'vue/server-renderer'
import { writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import CapiMascot, { type CapiMood } from '../src/components/brand/CapiMascot.vue'

const LABELS: Record<CapiMood, string> = {
  happy: 'Capi feliz',
  celebrating: 'Capi comemorando',
  thinking: 'Capi pensando',
  alert: 'Capi em alerta',
  sleeping: 'Capi dormindo',
}

it.runIf(process.env.EXPORT_CAPI)('exporta os humores da Capi', async () => {
  for (const mood of Object.keys(LABELS) as CapiMood[]) {
    const html = await renderToString(
      createSSRApp({ render: () => h(CapiMascot, { mood, size: 200, animated: false, label: LABELS[mood] }) }),
    )
    const svg = html
      .replace(/<!--[\s\S]*?-->/g, '')
      .replace(/ data-v-[a-z0-9]+(="")?/g, '')
      .replace(/ class="[^"]*"/g, '')
      .replace(/ data-(testid|mood)="[^"]*"/g, '')
      .replace('<svg ', '<svg xmlns="http://www.w3.org/2000/svg" ')
    writeFileSync(resolve(__dirname, `../../design-system/capi/capi-${mood}.svg`), svg + '\n')
  }
})
