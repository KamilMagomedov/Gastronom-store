
import React, {
  useCallback,
  useState,
} from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';
import { ConfirmModal } from '@/components/ui/confirm-modal';
import { useFocusEffect } from '@react-navigation/native';
import {
  ApiCustomerProfile,
  ApiService,
} from '@/services/api';

export default function ProfileScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { user, logout } = useAuth();
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const [showLogoutModal, setShowLogoutModal] = useState(false);
  const [profile, setProfile] = useState<ApiCustomerProfile | null>(null);
  const [profileLoading, setProfileLoading] = useState(true);

  useFocusEffect(
    useCallback(() => {
      if (!user?.token) {
        setProfileLoading(false);
        return;
      }

      let mounted = true;

      const loadProfile = async () => {
        setProfileLoading(true);

        try {
          const response =
            await ApiService.getProfile(
              user.token,
            );

          if (mounted) {
            setProfile(response.data);
          }
        } catch (error) {
          console.error(
            'Profile: failed to load profile',
            error,
          );
        } finally {
          if (mounted) {
            setProfileLoading(false);
          }
        }
      };

      loadProfile();

      return () => {
        mounted = false;
      };
    }, [user?.token]),
  );

  const confirmLogout = async () => {
    setShowLogoutModal(false);
    setIsLoggingOut(true);
    try {
      if (user?.token) {
        await ApiService.logout(user.token);
      }
    } catch {
      // Ignore server errors — still log out locally
    } finally {
      await logout();
      router.replace('/welcome');
    }
  };

  const menuItems = [
    { icon: 'person', label: 'Мои данные', route: '/profile/edit' },
    { icon: 'receipt.description.fill', label: 'История заказов', route: '/(tabs)/orders' },
    { icon: 'heart.fill', label: 'Избранное', route: '/favorites' },
    { icon: 'notifications', label: 'Уведомления', route: '/notifications' },
    { icon: 'tune', label: 'Настройки', route: '/settings' },
  ];

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />

      <ScrollView showsVerticalScrollIndicator={false}>
        <View style={styles.header}>
        <View
          style={[
            styles.avatarContainer,
            {
              borderColor: colors.primary,
              backgroundColor: colors.surface,
            },
          ]}
        >
          <ThemedText style={styles.avatarInitial}>
            {profile?.name
              ?.trim()
              .charAt(0)
              .toUpperCase() || '?'}
          </ThemedText>
        </View>
          <ThemedText style={styles.userName}>
            {profileLoading
              ? 'Загрузка...'
              : profile?.name || 'Пользователь'}
          </ThemedText>

          <ThemedText
            style={[
              styles.userEmail,
              { color: colors.textSub },
            ]}
          >
            {profile?.email || ''}
          </ThemedText>
        </View>

        <View style={styles.menuContainer}>
          {menuItems.map((item, index) => (
            <TouchableOpacity
              key={index}
              style={[styles.menuItem, { borderBottomColor: colors.border }]}
              onPress={() => router.push(item.route as any)}
            >
              <View style={styles.menuItemLeft}>
                <View style={[styles.menuIconContainer, { backgroundColor: colorScheme === 'dark' ? 'rgba(255,255,255,0.05)' : '#f3f4f6' }]}>
                    <IconSymbol name={item.icon as any} size={22} color={item.icon === 'heart.fill' ? '#ef4444' : colors.text} />
                </View>
                <ThemedText style={styles.menuLabel}>{item.label}</ThemedText>
              </View>
              <IconSymbol name="chevron.right" size={20} color={colors.textSub} />
            </TouchableOpacity>
          ))}
        </View>

        {/* Logout Button */}
        <TouchableOpacity
          style={[styles.logoutButton, isLoggingOut && { opacity: 0.5 }]}
          onPress={() => setShowLogoutModal(true)}
          disabled={isLoggingOut}
        >
          <ThemedText style={styles.logoutText}>
            {isLoggingOut ? 'Выход...' : 'Выйти из аккаунта'}
          </ThemedText>
        </TouchableOpacity>

        <View style={{ height: 40 }} />
      </ScrollView>

      <ConfirmModal
        visible={showLogoutModal}
        title="Выйти из аккаунта?"
        message="Вы уверены? Для доступа к приложению потребуется войти снова."
        confirmText="Выйти"
        cancelText="Отмена"
        destructive
        onConfirm={confirmLogout}
        onCancel={() => setShowLogoutModal(false)}
      />
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    alignItems: 'center',
    paddingVertical: 32,
    paddingHorizontal: 20,
  },
  avatarContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    borderWidth: 3,
    padding: 3,
    marginBottom: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  userName: {
    fontSize: 22,
    fontWeight: '800',
    marginBottom: 4,
  },
  userEmail: {
    fontSize: 14,
    marginBottom: 10,
  },
  menuContainer: {
    paddingHorizontal: 20,
    marginBottom: 32,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 16,
    borderBottomWidth: 1,
  },
  menuItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  menuIconContainer: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuLabel: {
    fontSize: 16,
    fontWeight: '600',
  },
  logoutButton: {
    marginHorizontal: 20,
    paddingVertical: 16,
    alignItems: 'center',
  },
  logoutText: {
    color: '#ef4444',
    fontSize: 16,
    fontWeight: '700',
  },
  avatarInitial: {
    fontSize: 36,
    fontWeight: '800',
  },
});
