import React from 'react';
import {
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
  Platform,
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { Colors } from '@/constants/theme';
import Animated, { FadeInUp } from 'react-native-reanimated';

export default function ReviewSuccessScreen() {
  const router = useRouter();
  const { productId } = useLocalSearchParams<{ productId: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const handleBackToProduct = () => {
    if (productId) {
      router.replace(`/product/${productId}`);
    } else {
      router.back();
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]} edges={['top', 'bottom']}>
      <View style={styles.header}>
        <View style={styles.headerSide} />
        <Text style={[styles.headerTitle, { color: colors.text }]}>Домашний гастроном</Text>
        <View style={styles.headerSide} />
      </View>

      <View style={styles.content}>
        <Animated.View entering={FadeInUp.duration(600)} style={styles.iconContainer}>
          <View style={[styles.blurBg, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]} />
          <View style={[styles.circle, { backgroundColor: colors.surface, borderColor: 'rgba(19, 236, 91, 0.1)' }]}>
            <IconSymbol name="checkmark" size={60} color={colors.primary} />
          </View>
        </Animated.View>

        <Text style={[styles.title, { color: colors.text }]}>Спасибо за ваш отзыв!</Text>
        <Text style={[styles.subtitle, { color: colors.textSub }]}>
          Он будет показан после модерации.
        </Text>
      </View>

      <View style={[styles.footer, { backgroundColor: colors.background, borderTopColor: colors.border }]}>
        <TouchableOpacity 
          style={[styles.primaryButton, { backgroundColor: colors.primary }]}
          onPress={() => router.replace('/(tabs)')}
        >
          <Text style={styles.primaryButtonText}>Продолжить покупки</Text>
          <IconSymbol name="cart.fill" size={20} color="#102216" />
        </TouchableOpacity>
        
        <TouchableOpacity 
          style={styles.secondaryButton}
          onPress={handleBackToProduct}
        >
          <Text style={[styles.secondaryButtonText, { color: colors.textSub }]}>Вернуться к товару</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 16,
    zIndex: 20,
  },
  headerSide: {
    width: 40,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    textAlign: 'center',
    flex: 1,
  },
  content: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    // Убрали отрицательный маржин, чтобы не накладывалось на хедер
    marginTop: -40, 
  },
  iconContainer: {
    width: 160,
    height: 160,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 32,
  },
  blurBg: {
    position: 'absolute',
    width: 140,
    height: 140,
    borderRadius: 70,
  },
  circle: {
    width: 120,
    height: 120,
    borderRadius: 60,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.04,
    shadowRadius: 30,
    elevation: 4,
  },
  title: {
    fontSize: 26,
    fontWeight: '800',
    marginBottom: 12,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 18,
    textAlign: 'center',
    lineHeight: 26,
    maxWidth: 280,
  },
  footer: {
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 20,
    borderTopWidth: 1,
    gap: 8,
  },
  primaryButton: {
    height: 60,
    borderRadius: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 12,
    elevation: 8,
  },
  primaryButtonText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#102216',
  },
  secondaryButton: {
    height: 50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    fontSize: 17,
    fontWeight: '600',
  },
});
