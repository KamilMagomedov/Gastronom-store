import { useTheme } from '@/context/theme-context';

export function useColorScheme() {
  const { theme } = useTheme();
  return theme;
}
