'use client'

import { useEffect, useState } from 'react'

interface GeolocationState {
  lat: number | null
  lng: number | null
  accuracy: number | null
  error: string | null
  loading: boolean
}

export function useGeolocation(): GeolocationState {
  const [state, setState] = useState<GeolocationState>({
    lat: null,
    lng: null,
    accuracy: null,
    error: null,
    loading: true,
  })

  useEffect(() => {
    let cancelled = false

    if (!navigator.geolocation) {
      setState((s) => ({ ...s, loading: false, error: 'Browser tidak mendukung GPS.' }))
      return
    }

    // getCurrentPosition is one-shot — no watchId to clear, but we guard
    // against setState after unmount with the cancelled flag.
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (cancelled) return
        setState({
          lat: pos.coords.latitude,
          lng: pos.coords.longitude,
          accuracy: pos.coords.accuracy,
          error: null,
          loading: false,
        })
      },
      (err) => {
        if (cancelled) return
        const messages: Record<number, string> = {
          1: 'Izin lokasi ditolak. Aktifkan GPS di pengaturan browser.',
          2: 'Lokasi tidak tersedia. Coba pindah ke tempat terbuka.',
          3: 'Waktu habis. Coba lagi.',
        }
        setState((s) => ({
          ...s,
          loading: false,
          error: messages[err.code] ?? 'GPS error.',
        }))
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    )

    return () => {
      cancelled = true
    }
  }, [])

  return state
}
