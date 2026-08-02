/**
 * Below are the colors that are used in the app. The colors are defined in the light and dark mode.
 * There are many other ways to style your app. For example, [Nativewind](https://www.nativewind.dev/), [Tamagui](https://tamagui.dev/), [unistyles](https://reactnativeunistyles.vercel.app), etc.
 */

import { Platform } from 'react-native';

const primary = '#13ec5b';
const primaryDark = '#0eb545';

export const Colors = {
  light: {
    text: '#111813',
    textSub: '#61896f',
    background: '#f6f8f6',
    surface: '#ffffff',
    primary: primary,
    primaryDark: primaryDark,
    tint: primaryDark,
    icon: '#61896f',
    tabIconDefault: '#61896f',
    tabIconSelected: primaryDark,
    border: '#e5e7eb',
  },
  dark: {
    text: '#ffffff',
    textSub: '#61896f',
    background: '#102216',
    surface: '#1a2c20',
    primary: primary,
    primaryDark: primaryDark,
    tint: primary,
    icon: '#9BA1A6',
    tabIconDefault: '#9BA1A6',
    tabIconSelected: primary,
    border: '#1f2937',
  },
};

export const Fonts = Platform.select({
  ios: {
    sans: 'system-ui',
    serif: 'ui-serif',
    rounded: 'ui-rounded',
    mono: 'ui-monospace',
  },
  default: {
    sans: 'normal',
    serif: 'serif',
    rounded: 'normal',
    mono: 'monospace',
  },
  web: {
    sans: "Plus Jakarta Sans, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
    serif: "Georgia, 'Times New Roman', serif",
    rounded: "'SF Pro Rounded', 'Hiragino Maru Gothic ProN', Meiryo, 'MS PGothic', sans-serif",
    mono: "SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
  },
});
