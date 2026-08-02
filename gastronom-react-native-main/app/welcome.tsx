
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { LinearGradient } from 'expo-linear-gradient';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback } from 'react';
import { Dimensions, ImageBackground, ScrollView, StyleSheet, TouchableOpacity, View } from 'react-native';
import { useAuth } from '@/context/auth-context';

const { height } = Dimensions.get('window');

export default function WelcomeScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { isAuthenticated } = useAuth();

  useFocusEffect(
    useCallback(() => {
      if (isAuthenticated) {
        router.replace('/(tabs)');
      }
    }, [isAuthenticated]),
  );

  return (
    <ThemedView style={styles.container}>
      <ScrollView 
        contentContainerStyle={styles.scrollContent}
        bounces={false}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.imageContainer}>
          <ImageBackground
            source={{ uri: "https://lh3.googleusercontent.com/aida-public/AB6AXuCUThdaUTmhfNWQLbf7q_nmZEFECssWc7MlT_zx5nrD0MVVvQgf2JYKj4n1j3288vJ1LVzA229hKvVTvF82No1UA2T8Ysas_3Vza8mPWoOdR2EoAupU4v-oP-33qm8f1GXYnva29m4BnZEPKBkSawupIsCz7t9-XMrac4Y3pe2ORujGtQb-49ypbPgp2oweYQHjgFrPhq50M7_AtROO06ImXeapmMMaOn93QnrIhGvL4G6MnIQQ_EAh6t2yZqaag9lk8NQhtecl37E" }}
            style={styles.backgroundImage}
          >
            <LinearGradient
              colors={['transparent', 'rgba(16, 34, 22, 0.8)']}
              style={styles.gradient}
            />
            <View style={styles.imageOverlayContent}>
              <View style={[styles.badge, { backgroundColor: 'rgba(19, 236, 91, 0.2)', borderColor: 'rgba(19, 236, 91, 0.3)' }]}>
                <IconSymbol name="leaf.fill" size={14} color="#13ec5b" />
                <ThemedText style={styles.badgeText}>ОРГАНИКА</ThemedText>
              </View>
              <ThemedText style={styles.brandName}>Домашний гастроном</ThemedText>
            </View>
          </ImageBackground>
        </View>

        <View style={styles.contentContainer}>
          <View style={styles.headerText}>
            <ThemedText style={styles.title}>
              Свежие продукты {'\n'}с доставкой на дом
            </ThemedText>
          </View>

          <View style={styles.buttonGroup}>
            <TouchableOpacity 
              style={[styles.primaryButton, { backgroundColor: Colors.light.primary }]}
              onPress={() => router.push('/signup')}
              activeOpacity={0.8}
            >
              <IconSymbol name="person.badge.plus" size={20} color="#111813" style={styles.buttonIcon} />
              <ThemedText style={styles.primaryButtonText}>Создать аккаунт</ThemedText>
            </TouchableOpacity>

            <TouchableOpacity 
              style={[styles.secondaryButton, { backgroundColor: colors.surface }]}
              onPress={() => router.push('/login')}
              activeOpacity={0.8}
            >
              <IconSymbol name="door.right.hand.open" size={20} color={colorScheme === 'dark' ? '#fff' : '#4b5563'} style={styles.buttonIcon} />
              <ThemedText style={styles.secondaryButtonText}>Войти в аккаунт</ThemedText>
            </TouchableOpacity>

            <TouchableOpacity 
              style={styles.linkButton}
              onPress={() => router.push('/forgot-password')}
              activeOpacity={0.7}
            >
              <ThemedText style={styles.linkButtonText}>Восстановить пароль</ThemedText>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
  },
  imageContainer: {
    width: '100%',
    height: height * 0.55,
    overflow: 'hidden',
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
    elevation: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 10,
  },
  backgroundImage: {
    width: '100%',
    height: '100%',
  },
  gradient: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: '50%',
  },
  imageOverlayContent: {
    flex: 1,
    justifyContent: 'flex-end',
    padding: 24,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
    alignSelf: 'flex-start',
    marginBottom: 8,
  },
  badgeText: {
    color: '#13ec5b',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1,
  },
  brandName: {
    color: '#FFFFFF',
    fontSize: 36,
    fontWeight: '800',
    letterSpacing: -1,
  },
  contentContainer: {
    padding: 24,
    flex: 1,
    justifyContent: 'space-between',
  },
  headerText: {
    paddingVertical: 12,
  },
  title: {
    fontSize: 28,
    fontWeight: '700',
    lineHeight: 34,
  },
  buttonGroup: {
    gap: 12,
    marginTop: 24,
    paddingBottom: 24,
  },
  primaryButton: {
    flexDirection: 'row',
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  primaryButtonText: {
    color: '#111813',
    fontSize: 18,
    fontWeight: '700',
  },
  secondaryButton: {
    flexDirection: 'row',
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'transparent',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  secondaryButtonText: {
    fontSize: 18,
    fontWeight: '700',
  },
  buttonIcon: {
    marginRight: 8,
  },
  linkButton: {
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  linkButtonText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
  },
});
