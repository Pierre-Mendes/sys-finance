<template>
  <section class="mt-8 w-full max-w-4xl bg-white rounded-2xl shadow-lg shadow-gray-200 border border-gray-100 overflow-hidden text-gray-800">
    <header class="px-6 py-5 border-b border-gray-100 bg-gray-50">
      <h3 class="font-semibold text-lg">Notificações e lembretes</h3>
      <p class="text-sm text-gray-500">Avisos de contas a vencer no sino do app e, se quiser, no celular.</p>
    </header>

    <div class="p-6 md:p-8 space-y-8">
      <!-- Push deste dispositivo -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
        <div>
          <p class="font-semibold">Notificações neste dispositivo</p>
          <p class="text-sm text-gray-500">{{ pushDescription }}</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          <button v-if="push.state.value === 'off'" @click="enablePush" :disabled="push.busy.value" class="bg-primary hover:bg-brand-800 text-white font-semibold px-4 py-2.5 rounded-lg disabled:opacity-50">Ativar</button>
          <template v-else-if="push.state.value === 'on'">
            <button @click="sendTest" :disabled="testing" class="border border-gray-200 hover:bg-gray-50 font-medium px-4 py-2.5 rounded-lg disabled:opacity-50">Enviar teste</button>
            <button @click="disablePush" :disabled="push.busy.value" class="text-red-600 hover:bg-red-50 font-medium px-4 py-2.5 rounded-lg disabled:opacity-50">Desativar</button>
          </template>
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Preferências (valem para todos os dispositivos) -->
      <form @submit.prevent="save" class="space-y-6">
        <fieldset>
          <legend class="font-semibold mb-1">Quando avisar</legend>
          <p class="text-sm text-gray-500 mb-3">Dias antes do vencimento de cada conta pendente.</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="opt in DAY_OPTIONS" :key="opt.value" :class="['px-3 py-2 rounded-lg border text-sm cursor-pointer select-none', settings.remindDays.includes(opt.value) ? 'bg-blue-50 border-blue-300 text-blue-800 font-semibold' : 'border-gray-200 text-gray-600']">
              <input type="checkbox" class="sr-only" :value="opt.value" v-model="settings.remindDays" />
              {{ opt.label }}
            </label>
          </div>
          <label class="mt-4 flex items-center gap-3 text-sm cursor-pointer">
            <input type="checkbox" v-model="settings.notifyOverdue" class="w-4 h-4 accent-blue-600" />
            Avisar uma vez quando a conta atrasar
          </label>
        </fieldset>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <label class="block">
            <span class="block text-sm font-semibold mb-2">Horário dos avisos</span>
            <select v-model.number="settings.reminderHour" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
              <option v-for="h in 24" :key="h - 1" :value="h - 1">{{ String(h - 1).padStart(2, '0') }}:00</option>
            </select>
          </label>
          <label class="flex items-center gap-3 text-sm cursor-pointer sm:mt-8">
            <input type="checkbox" v-model="settings.pushEnabled" class="w-4 h-4 accent-blue-600" />
            Enviar também por push ({{ devices }} {{ devices === 1 ? 'dispositivo' : 'dispositivos' }})
          </label>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="saving" class="bg-primary hover:bg-brand-800 text-white font-bold py-3 px-8 rounded-lg shadow-md disabled:opacity-50">Salvar lembretes</button>
        </div>
      </form>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue3-toastify'
import api from '@/data/api/HttpClient'
import { usePushNotifications } from '@/presentation/composables/usePushNotifications'

const DAY_OPTIONS = [
  { value: 7, label: '7 dias antes' },
  { value: 3, label: '3 dias antes' },
  { value: 1, label: 'Na véspera' },
  { value: 0, label: 'No dia' },
]

const push = usePushNotifications()
const settings = ref({ remindDays: [3, 1, 0] as number[], notifyOverdue: true, pushEnabled: true, reminderHour: 8 })
const devices = ref(0)
const saving = ref(false)
const testing = ref(false)

const pushDescription = computed(() => ({
  on: 'Ativadas. Os lembretes chegam mesmo com o app fechado.',
  off: 'Desativadas neste aparelho. Você ainda recebe os avisos no sino do app.',
  denied: 'Bloqueadas pelo navegador. Libere as notificações deste site nas configurações do navegador.',
  'needs-install': 'No iPhone, instale o app primeiro: toque em Compartilhar → "Adicionar à Tela de Início" e abra por lá.',
  unsupported: 'Este navegador não suporta notificações push.',
}[push.state.value]))

const load = async () => {
  try {
    const { data } = await api.get('/api/reminders/settings')
    const { devices: count, ...prefs } = data.data
    settings.value = prefs
    devices.value = count
  } catch {
    toast.error('Não foi possível carregar as preferências de lembrete.')
  }
}

const save = async () => {
  saving.value = true
  try {
    await api.put('/api/reminders/settings', settings.value)
    toast.success('Lembretes salvos!')
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Erro ao salvar lembretes.')
  } finally {
    saving.value = false
  }
}

const enablePush = async () => {
  try {
    if (await push.enable()) {
      toast.success('Notificações ativadas neste dispositivo.')
      await load()
    } else if (push.state.value === 'denied') {
      toast.warning('Permissão negada pelo navegador.')
    }
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Não foi possível ativar as notificações.')
  }
}

const disablePush = async () => {
  try {
    await push.disable()
    toast.info('Notificações desativadas neste dispositivo.')
    await load()
  } catch {
    toast.error('Não foi possível desativar.')
  }
}

const sendTest = async () => {
  testing.value = true
  try {
    await api.post('/api/reminders/push/test')
    toast.success('Notificação de teste enviada.')
  } catch (e: any) {
    toast.error(e.response?.data?.error || 'Falha ao enviar o teste.')
  } finally {
    testing.value = false
  }
}

onMounted(async () => {
  await Promise.all([load(), push.refresh()])
})
</script>
