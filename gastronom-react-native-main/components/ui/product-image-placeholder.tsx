import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import React from 'react';
import { StyleSheet, Text, View, ViewStyle } from 'react-native';

interface ProductImagePlaceholderProps {
  size?: 'small' | 'large';
  style?: ViewStyle;
}

export function ProductImagePlaceholder({ size = 'large', style }: ProductImagePlaceholderProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const isSmall = size === 'small';

  const bg = colorScheme === 'dark' ? '#1a2e21' : '#f0fdf4';
  const borderColor = colorScheme === 'dark' ? '#2d4a35' : '#bbf7d0';
  const iconColor = '#13ec5b';
  const textColor = colorScheme === 'dark' ? '#4ade80' : '#16a34a';

  return (
    <View style={[styles.container, { backgroundColor: bg, borderColor }, style]}>
      {/* Camera icon built from Views */}
      <View style={styles.iconWrap}>
        <View style={[styles.cameraBody, { borderColor: iconColor }]}>
          <View style={[styles.lens, { borderColor: iconColor }]} />
          <View style={[styles.lensInner, { backgroundColor: iconColor, opacity: 0.25 }]} />
        </View>
        <View style={[styles.cameraNotch, { backgroundColor: bg, borderColor: iconColor }]} />
      </View>

      <Text
        style={[
          styles.text,
          { color: textColor, fontSize: isSmall ? 9 : 11 },
        ]}
        numberOfLines={2}
        textBreakStrategy="balanced"
      >
        Фото в процессе{'\n'}подготовки
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderRadius: 10,
    padding: 8,
  },
  iconWrap: {
    position: 'relative',
    alignItems: 'center',
  },
  cameraBody: {
    width: 28,
    height: 20,
    borderRadius: 5,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cameraNotch: {
    position: 'absolute',
    top: -5,
    left: 6,
    width: 8,
    height: 5,
    borderTopLeftRadius: 3,
    borderTopRightRadius: 3,
    borderWidth: 2,
    borderBottomWidth: 0,
  },
  lens: {
    width: 10,
    height: 10,
    borderRadius: 5,
    borderWidth: 2,
  },
  lensInner: {
    position: 'absolute',
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  text: {
    fontWeight: '600',
    textAlign: 'center',
    lineHeight: 14,
    letterSpacing: 0.1,
  },
});
