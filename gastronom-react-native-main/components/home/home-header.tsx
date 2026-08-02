import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export function HomeHeader() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={styles.leftContent}>
        <View style={styles.avatarContainer}>
          <Image
            source={{ uri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmdd1OWcD_3M5ECJCKIkb7E4HojrchWKOppsw2LjRu_skbvMsKD38Ua4m3kMSKaAO8jY_4UoL1MkNracbWhQ3asJ2UBUyKEWx2FyVEW8Fzb7P-8ipS3ac_Pp_axmrh6h8zPOrt7yPKm0Vw0iymA4n2sgBn_w7abD_DjoN-qatmjMS23iwNa3TNPH0ilG36RKx_Rnu8nunPWLE8l4fSHnGoAIGvh4wYeEheUk9Ge7w4Hu9mY--kozPFE0ECxjX4fWE5qnUE0TOkLmY' }}
            style={[styles.avatar, { borderColor: colors.surface }]}
          />
          <View style={[styles.onlineBadge, { borderColor: colors.background, backgroundColor: colors.primary }]} />
        </View>
        <View style={styles.addressContainer}>
          <Text style={[styles.deliveryLabel, { color: colors.textSub }]}>Доставка:</Text>
          <TouchableOpacity style={styles.addressRow}>
            <Text style={[styles.addressText, { color: colors.text }]} numberOfLines={1}>
              ул. Зеленая, 123, Москва
            </Text>
            <IconSymbol name="chevron.down" size={18} color={colors.primary} />
          </TouchableOpacity>
        </View>
      </View>
      <TouchableOpacity 
        onPress={() => router.push('/notifications')}
        style={[styles.notificationButton, { backgroundColor: colors.surface }]}
      >
        <IconSymbol name="bell" size={24} color={colors.text} />
        <View style={[styles.dot, { borderColor: colors.surface }]} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 50,
    paddingBottom: 8,
    gap: 16,
  },
  leftContent: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    gap: 12,
  },
  avatarContainer: {
    position: 'relative',
  },
  avatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 2,
  },
  onlineBadge: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 12,
    height: 12,
    borderRadius: 6,
    borderWidth: 2,
  },
  addressContainer: {
    flex: 1,
  },
  deliveryLabel: {
    fontSize: 10,
    fontWeight: '500',
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  addressText: {
    fontSize: 14,
    fontWeight: '700',
    flexShrink: 1,
  },
  notificationButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  dot: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#ef4444',
    borderWidth: 1,
  },
});
