'use client'

import { useState, useRef } from 'react'
import { toast } from 'sonner'
import { MapPin, Clock, Loader2, AlertCircle, CheckCircle2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useGeolocation } from '@/hooks/useGeolocation'
import { haversineDistance, generateDeviceHash } from '@/lib/utils/geo'
import { useLocations } from '@/lib/api/locations'
import { useTodayAttendance, useClockIn, useClockOut } from '@/lib/api/attendance'
import type { Location, Attendance } from '@/types'

const STATUS_LABEL: Record<string, string> = {
  PRESENT: 'Hadir',
  LATE: 'Terlambat',
  ABSENT: 'Tidak Hadir',
  LEAVE: 'Cuti',
}

const STATUS_COLOR: Record<string, string> = {
  PRESENT: 'bg-green-100 text-green-700',
  LATE: 'bg-yellow-100 text-yellow-700',
  ABSENT: 'bg-red-100 text-red-700',
  LEAVE: 'bg-blue-100 text-blue-700',
}

export default function AttendancePage() {
  const [selectedLocationId, setSelectedLocationId] = useState<string>('')
  const clockingIn = useRef(false)

  const geo = useGeolocation()
  const { data: todayData, isPending: loadingToday } = useTodayAttendance()
  const { data: locationsData } = useLocations()
  const clockIn = useClockIn()
  const clockOut = useClockOut()

  const today: Attendance | null = todayData?.data ?? null
  const locations: Location[] = locationsData?.data ?? []

  const selectedLocation = locations.find((l) => l.id === selectedLocationId)
  const distance =
    geo.lat && geo.lng && selectedLocation
      ? Math.round(haversineDistance(geo.lat, geo.lng, selectedLocation.latitude, selectedLocation.longitude))
      : null

  const withinRadius = distance !== null && selectedLocation
    ? distance <= selectedLocation.radiusMeters
    : null

  const handleClockIn = async () => {
    if (clockingIn.current) return
    if (!geo.lat || !geo.lng) {
      toast.error('Lokasi GPS belum tersedia.')
      return
    }
    clockingIn.current = true
    try {
      await clockIn.mutateAsync({
        lat: geo.lat,
        lng: geo.lng,
        device_hash: generateDeviceHash(),
        location_id: selectedLocationId || undefined,
      })
      toast.success('Clock-in berhasil!')
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Clock-in gagal.'
      toast.error(msg)
    } finally {
      clockingIn.current = false
    }
  }

  const handleClockOut = async () => {
    try {
      await clockOut.mutateAsync({ lat: geo.lat ?? undefined, lng: geo.lng ?? undefined })
      toast.success('Clock-out berhasil!')
    } catch (err: unknown) {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Clock-out gagal.'
      toast.error(msg)
    }
  }

  const now = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })

  return (
    <div className="space-y-6 max-w-xl">
      <h1 className="text-2xl font-bold text-gray-900">Absensi</h1>

      {/* Today's status */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Status Hari Ini</CardTitle>
        </CardHeader>
        <CardContent>
          {loadingToday ? (
            <div className="h-12 bg-gray-100 rounded animate-pulse" />
          ) : today ? (
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <span
                  className={`inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium ${STATUS_COLOR[today.status] ?? ''}`}
                >
                  {STATUS_LABEL[today.status] ?? today.status}
                </span>
              </div>
              <div className="flex gap-6 text-sm text-gray-600">
                {today.clockIn && (
                  <span className="flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" />
                    Masuk: {today.clockIn.slice(0, 5)}
                  </span>
                )}
                {today.clockOut && (
                  <span className="flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" />
                    Keluar: {today.clockOut.slice(0, 5)}
                  </span>
                )}
              </div>
            </div>
          ) : (
            <p className="text-sm text-gray-500">Belum absen hari ini. Waktu sekarang: {now}</p>
          )}
        </CardContent>
      </Card>

      {/* GPS status */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-base">Lokasi GPS</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {geo.loading && (
            <div className="flex items-center gap-2 text-sm text-gray-500">
              <Loader2 className="h-4 w-4 animate-spin" />
              Mendapatkan lokasi...
            </div>
          )}
          {geo.error && (
            <div className="flex items-start gap-2 text-sm text-red-600">
              <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
              <span>{geo.error}</span>
            </div>
          )}
          {geo.lat && geo.lng && (
            <div className="flex items-center gap-2 text-sm text-green-700">
              <CheckCircle2 className="h-4 w-4" />
              <span>
                {geo.lat.toFixed(5)}, {geo.lng.toFixed(5)}
                {geo.accuracy && ` (±${Math.round(geo.accuracy)}m)`}
              </span>
            </div>
          )}

          {/* Location picker */}
          {locations.length > 0 && (
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-gray-700">Lokasi Kantor</label>
              <Select value={selectedLocationId} onValueChange={setSelectedLocationId}>
                <SelectTrigger>
                  <SelectValue placeholder="Pilih lokasi..." />
                </SelectTrigger>
                <SelectContent>
                  {locations.map((loc) => (
                    <SelectItem key={loc.id} value={loc.id}>
                      {loc.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}

          {/* Distance info */}
          {distance !== null && selectedLocation && (
            <div className={`flex items-center gap-2 text-sm ${withinRadius ? 'text-green-700' : 'text-orange-600'}`}>
              <MapPin className="h-4 w-4" />
              <span>
                Jarak: {distance}m dari {selectedLocation.name}
                {withinRadius
                  ? ' ✓ Dalam radius'
                  : ` (radius: ${selectedLocation.radiusMeters}m)`}
              </span>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Actions */}
      <div className="flex gap-3">
        {!today?.clockIn && (
          <Button
            onClick={handleClockIn}
            disabled={geo.loading || !!geo.error || clockIn.isPending}
            className="flex-1"
          >
            {clockIn.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
            Clock In
          </Button>
        )}
        {today?.clockIn && !today.clockOut && (
          <Button
            variant="outline"
            onClick={handleClockOut}
            disabled={clockOut.isPending}
            className="flex-1"
          >
            {clockOut.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
            Clock Out
          </Button>
        )}
        {today?.clockIn && today.clockOut && (
          <div className="flex-1 text-center">
            <Badge variant="secondary" className="text-sm px-4 py-2">
              Absensi selesai hari ini
            </Badge>
          </div>
        )}
      </div>
    </div>
  )
}
