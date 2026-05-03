import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

// Routes that anyone can access without a token
const PUBLIC_ROUTES = ['/login', '/forgot-password']

// Prefixes that require authentication
const PROTECTED_PREFIXES = [
  '/overview',
  '/employees',
  '/attendance',
  '/leave',
  '/payroll',
  '/settings',
]

export function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl

  const token = request.cookies.get('auth_token')?.value

  const isPublicRoute = PUBLIC_ROUTES.some(
    (route) => pathname === route || pathname.startsWith(route + '/')
  )

  const isProtectedRoute = PROTECTED_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(prefix + '/')
  )

  // Redirect unauthenticated users away from protected routes
  if (isProtectedRoute && !token) {
    const loginUrl = new URL('/login', request.url)
    loginUrl.searchParams.set('callbackUrl', pathname)
    return NextResponse.redirect(loginUrl)
  }

  // Redirect authenticated users away from auth pages
  if (isPublicRoute && token) {
    return NextResponse.redirect(new URL('/overview', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|sitemap.xml|robots.txt).*)',
  ],
}
