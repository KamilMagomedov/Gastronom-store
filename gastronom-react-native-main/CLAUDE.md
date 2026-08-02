# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development
npm start              # Start Expo dev server
npm run ios            # Run on iOS simulator
npm run android        # Run on Android emulator/device
npm run web            # Run in browser

# Code quality
npm run lint           # Run ESLint
```

No test runner is configured. No separate production build script — use EAS CLI (`eas build`) for production builds.

## Architecture

**Framework:** React Native with Expo (SDK 54), using **Expo Router** for file-based routing (similar to Next.js). New Architecture and React Compiler are enabled.

**Routing:** All screens live in `/app/`. The root layout (`app/_layout.tsx`) wraps everything in `AuthContext` and `ThemeContext` providers, then uses a `Stack` navigator. Authenticated screens are nested inside `app/(tabs)/` with a bottom tab bar (5 tabs: Home, Search, Cart, Profile, plus hidden tabs).

**State management:** React Context API only — no Redux/Zustand.
- `context/auth-context.tsx` — token-based auth persisted via AsyncStorage; exposes `useAuth()` with `user`, `isAuthentested`, `isLoading`, `login(token)`, `logout()`
- `context/theme-context.tsx` — light/dark/system theme; exposes `useTheme()` with `theme`, `themeMode`, `setThemeMode()`

**API layer:** `services/api.ts` — static class targeting `http://green-market.test/api/v1/customers`. Currently has `register()`. Add new API calls here.

**Theme system:** Colors and fonts are defined in `constants/theme.ts`. Use `useTheme()` to access the current theme object. Theme-aware primitives: `<ThemedText>`, `<ThemedView>`.

**Path alias:** `@/*` resolves to the repo root (configured in `tsconfig.json`).

## Key Conventions

- Component files use kebab-case (e.g. `product-card.tsx`)
- Screen-specific components are co-located under `components/<screen-name>/`
- Icons use Expo Vector Icons via `components/ui/icon-symbol.tsx` (platform-aware: `.ios.tsx` variant exists)
- Dynamic routes follow Expo Router convention: `app/product/[id].tsx`, `app/order/[id].tsx`
