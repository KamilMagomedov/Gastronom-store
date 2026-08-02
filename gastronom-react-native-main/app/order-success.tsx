import React, { useEffect } from 'react';
import {
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
  Platform,
  ScrollView,
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { Colors } from '@/constants/theme';
import Animated, { 
  useSharedValue, 
  useAnimatedStyle, 
  withRepeat, 
  withTiming, 
  Easing,
  FadeInUp
} from 'react-native-reanimated';
import MobileMap from '@/components/MobileMap';

export default function OrderSuccessScreen() {
  const router = useRouter();
  const { orderId, totalPrice, deliveryDate, deliveryTime, address } = useLocalSearchParams<{
    orderId: string;
    totalPrice: string;
    deliveryDate: string;
    deliveryTime: string;
    address: string;
  }>();

  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const rotation = useSharedValue(0);

  useEffect(() => {
    rotation.value = withRepeat(
      withTiming(360, {
        duration: 10000,
        easing: Easing.linear,
      }),
      -1,
      false
    );
  }, []);

  const animatedRingStyle = useAnimatedStyle(() => {
    return {
      transform: [{ rotate: `${rotation.value}deg` }],
    };
  });

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]} edges={['top', 'bottom']}>
      {/* Decorative Background Elements */}
      <View style={[styles.gradientTop, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.05)' : 'rgba(19, 236, 91, 0.1)' }]} />
      <View style={[styles.blurCircle1, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]} />
      <View style={[styles.blurCircle2, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]} />

      <ScrollView 
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <Animated.View entering={FadeInUp.delay(200).duration(600)} style={styles.successHeader}>
          <View style={styles.iconContainer}>
            <Animated.View style={[styles.dashedRing, { borderColor: 'rgba(19, 236, 91, 0.3)' }, animatedRingStyle]} />
            <View style={[styles.iconBg, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
              <View style={[styles.checkCircle, { backgroundColor: colors.primary }]}>
                <IconSymbol name="star.fill" size={40} color="#fff" />
              </View>
            </View>
          </View>
          
          <View style={styles.titleContainer}>
            <Text style={[styles.title, { color: colors.text }]}>Спасибо за{'\n'}ваш заказ!</Text>
            <Text style={[styles.subtitle, { color: colors.textSub }]}>
              Мы получили ваш заказ и уже начали собирать самые свежие продукты.
            </Text>
          </View>
        </Animated.View>

        <View style={styles.infoGrid}>
          <View style={[styles.infoCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.infoIconWrapper, { backgroundColor: colorScheme === 'dark' ? '#2a3e30' : '#f1f5f9' }]}>
              <IconSymbol name="house.fill" size={20} color={colors.textSub} />
            </View>
            <Text style={[styles.infoLabel, { color: colors.textSub }]}>НОМЕР ЗАКАЗА</Text>
            <Text style={[styles.infoValue, { color: colors.text }]}>#{orderId}</Text>
          </View>
          
          <View style={[styles.infoCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.infoIconWrapper, { backgroundColor: colorScheme === 'dark' ? '#2a3e30' : '#f1f5f9' }]}>
              <IconSymbol name="paperplane.fill" size={20} color={colors.textSub} />
            </View>
            <Text style={[styles.infoLabel, { color: colors.textSub }]}>СУММА</Text>
            <Text style={[styles.infoValue, { color: colors.text }]}>{totalPrice} ₽</Text>
          </View>
        </View>

        <View style={[styles.deliveryBanner, { backgroundColor: colors.surface, borderColor: 'rgba(19, 236, 91, 0.2)' }]}>
          <View style={[styles.deliveryIcon, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.2)' : 'rgba(19, 236, 91, 0.1)' }]}>
            <IconSymbol name="star" size={24} color={colors.primaryDark} />
          </View>
          <View style={styles.deliveryText}>
            <Text style={[styles.deliveryDate, { color: colors.textSub }]}>Доставка: {deliveryDate}</Text>
            <Text style={[styles.deliveryTime, { color: colors.text }]}>{deliveryTime}</Text>
          </View>
        </View>

        <View style={[styles.mapSection, { borderColor: colors.border }]}>
          {Platform.OS === 'web' ? (
            <iframe
              src={`https://yandex.ru/map-widget/v1/?text=${encodeURIComponent(address)}&z=15`}
              width="100%"
              height="128"
              style={{ border: 0, borderRadius: 20 }}
              allowFullScreen
            />
          ) : (
            <View style={StyleSheet.absoluteFill}>
              <MobileMap apiKey="d09d333a-47cc-46e9-9f40-ca543b5ff126" />
            </View>
          )}
          
          <View style={styles.mapOverlay}>
            <View style={styles.addressBar}>
              <IconSymbol name="paperplane.fill" size={18} color={colors.primary} />
              <Text style={styles.addressText} numberOfLines={1}>{address}</Text>
            </View>
          </View>
        </View>
      </ScrollView>

      <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.border }]}>
        <TouchableOpacity style={[styles.primaryButton, { backgroundColor: colors.primary }]}>
          <IconSymbol name="paperplane.fill" size={20} color="#102216" />
          <Text style={styles.primaryButtonText}>Отследить заказ</Text>
        </TouchableOpacity>
        <TouchableOpacity 
          style={[styles.secondaryButton, { backgroundColor: colorScheme === 'dark' ? '#2a3e30' : '#f1f5f9' }]}
          onPress={() => router.replace('/(tabs)')}
        >
          <Text style={[styles.secondaryButtonText, { color: colors.text }]}>Вернуться на главную</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    position: 'relative',
    overflow: 'hidden',
  },
  gradientTop: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 256,
  },
  blurCircle1: {
    position: 'absolute',
    top: -40,
    right: -40,
    width: 160,
    height: 160,
    borderRadius: 80,
  },
  blurCircle2: {
    position: 'absolute',
    top: 80,
    left: -40,
    width: 128,
    height: 128,
    borderRadius: 64,
  },
  scrollContent: {
    paddingHorizontal: 24,
    paddingTop: 24,
    paddingBottom: 24,
    alignItems: 'center',
  },
  successHeader: {
    alignItems: 'center',
    marginBottom: 32,
    width: '100%',
  },
  iconContainer: {
    width: 128,
    height: 128,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  dashedRing: {
    position: 'absolute',
    width: 128,
    height: 128,
    borderRadius: 64,
    borderWidth: 4,
    borderStyle: 'dashed',
  },
  iconBg: {
    width: 112,
    height: 112,
    borderRadius: 56,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  titleContainer: {
    alignItems: 'center',
    gap: 8,
  },
  title: {
    fontSize: 32,
    fontWeight: '800',
    textAlign: 'center',
    lineHeight: 38,
  },
  subtitle: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
    maxWidth: 280,
  },
  infoGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 24,
    width: '100%',
  },
  infoCard: {
    flex: 1,
    padding: 16,
    borderRadius: 20,
    borderWidth: 1,
    gap: 4,
  },
  infoIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  infoLabel: {
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  infoValue: {
    fontSize: 18,
    fontWeight: '700',
  },
  deliveryBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    width: '100%',
    padding: 20,
    borderRadius: 20,
    borderWidth: 1,
    gap: 16,
    marginBottom: 24,
  },
  deliveryIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
  deliveryText: {
    flex: 1,
  },
  deliveryDate: {
    fontSize: 14,
    fontWeight: '500',
    marginBottom: 2,
  },
  deliveryTime: {
    fontSize: 20,
    fontWeight: '800',
  },
  mapSection: {
    width: '100%',
    height: 128,
    borderRadius: 20,
    overflow: 'hidden',
    borderWidth: 1,
    position: 'relative',
  },
  mapBackground: {
    flex: 1,
  },
  mapOverlay: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0,0,0,0.5)',
    padding: 12,
  },
  addressBar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  addressText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
    flex: 1,
  },
  footer: {
    width: '100%',
    padding: 20,
    paddingBottom: Platform.OS === 'ios' ? 40 : 24,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    gap: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 10,
  },
  primaryButton: {
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  primaryButtonText: {
    color: '#102216',
    fontSize: 18,
    fontWeight: '700',
  },
  secondaryButton: {
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    fontSize: 16,
    fontWeight: '600',
  },
});
