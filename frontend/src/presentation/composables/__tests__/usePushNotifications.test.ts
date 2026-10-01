import { describe, it, expect } from 'vitest'
import { urlBase64ToUint8Array, isIos } from '../usePushNotifications'

describe('usePushNotifications helpers', () => {
  it('converte a chave VAPID base64url (sem padding) em bytes', () => {
    // "-_8" em base64url = bytes 0xFB 0xFF 0xFF... usa os dois caracteres que diferem do base64 comum
    expect(Array.from(urlBase64ToUint8Array('-_8'))).toEqual([0xfb, 0xff])
    expect(Array.from(urlBase64ToUint8Array('AQID'))).toEqual([1, 2, 3])
  })

  it('reconhece iPhone para orientar a instalação na tela inicial', () => {
    expect(isIos('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')).toBe(true)
    expect(isIos('Mozilla/5.0 (Linux; Android 14)')).toBe(false)
  })
})
