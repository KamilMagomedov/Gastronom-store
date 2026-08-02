
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useFocusEffect } from '@react-navigation/native';
import { useRouter } from 'expo-router';
import React, { useCallback, useEffect, useRef } from 'react';
import { Animated, Easing, StyleSheet, View } from 'react-native';

export default function SplashScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { isAuthenticated, isLoading } = useAuth();

  const fadeAnim = useRef(new Animated.Value(0)).current;
  const scaleAnim = useRef(new Animated.Value(0.9)).current;
  const progressAnim = useRef(new Animated.Value(0)).current;
  const [animationDone, setAnimationDone] = React.useState(false);
  const navigatedRef = useRef(false);

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 800,
        useNativeDriver: true,
      }),
      Animated.spring(scaleAnim, {
        toValue: 1,
        friction: 8,
        tension: 40,
        useNativeDriver: true,
      }),
    ]).start();

    Animated.timing(progressAnim, {
      toValue: 1,
      duration: 2500,
      easing: Easing.inOut(Easing.quad),
      useNativeDriver: false,
    }).start(() => setAnimationDone(true));
  }, []);

  // Reset navigation flag when screen becomes focused
  useFocusEffect(
    useCallback(() => {
      navigatedRef.current = false;
      return () => {};
    }, [])
  );

  // Navigate only when BOTH animation is done AND auth is resolved —
  // always reads the current isAuthenticated (no stale closure).
  // navigatedRef guards against re-running if isAuthenticated changes after navigation.
  useEffect(() => {
    if (!animationDone || isLoading || navigatedRef.current) return;
    navigatedRef.current = true;
    if (isAuthenticated) {
      router.replace('/(tabs)');
    } else {
      router.replace('/welcome');
    }
  }, [animationDone, isLoading, isAuthenticated]);

  const progressWidth = progressAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0%', '100%'],
  });

  return (
    <ThemedView style={[styles.container, { backgroundColor: colors.background }]}>
      {/* Decorative Background Elements */}
      <View style={styles.decorativeContainer}>
        <View style={[styles.blob, styles.topLeftBlob, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.05)' : 'rgba(19, 236, 91, 0.1)' }]} />
        <View style={[styles.blob, styles.bottomRightBlob, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.05)' : 'rgba(19, 236, 91, 0.1)' }]} />
      </View>

      <View style={styles.content}>
        <View style={styles.topSpacer} />
        
        <Animated.View style={[
          styles.brandContainer,
          {
            opacity: fadeAnim,
            transform: [{ scale: scaleAnim }]
          }
        ]}>
          <View style={styles.logoWrapper}>
            <View style={[styles.logoGlow, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]} />
            <View style={styles.logoBox}>
              <IconSymbol name="bag.fill" size={80} color="#FFFFFF" />
            </View>
            <View style={[styles.accentBadge, { backgroundColor: colors.surface, borderColor: colors.background }]}>
              <IconSymbol name="bolt.fill" size={24} color="#13ec5b" />
            </View>
          </View>

          <View style={styles.textContainer}>
            <ThemedText style={styles.title}>Домашний гастроном</ThemedText>
            <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
              Свежие продукты
            </ThemedText>
          </View>
        </Animated.View>

        <View style={styles.bottomSpacer} />

        <View style={styles.loaderArea}>
          <View style={[styles.progressBarContainer, { backgroundColor: colorScheme === 'dark' ? '#1a2e21' : '#e2e8f0' }]}>
            <Animated.View style={[styles.progressBar, { width: progressWidth }]} />
          </View>
          <ThemedText style={[styles.loadingText, { color: colors.textSub }]}>
            ЗАГРУЗКА...
          </ThemedText>
        </View>
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  decorativeContainer: {
    ...StyleSheet.absoluteFillObject,
    overflow: 'hidden',
  },
  blob: {
    position: 'absolute',
    width: 400,
    height: 400,
    borderRadius: 200,
  },
  topLeftBlob: {
    top: -100,
    left: -100,
  },
  bottomRightBlob: {
    bottom: -100,
    right: -100,
  },
  content: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 24,
  },
  topSpacer: {
    flex: 1,
  },
  bottomSpacer: {
    flex: 1,
  },
  brandContainer: {
    alignItems: 'center',
    gap: 32,
    width: '100%',
  },
  logoWrapper: {
    position: 'relative',
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoGlow: {
    position: 'absolute',
    width: 160,
    height: 160,
    borderRadius: 40,
  },
  logoBox: {
    width: 144,
    height: 144,
    backgroundColor: '#13ec5b',
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 20 },
    shadowOpacity: 0.3,
    shadowRadius: 30,
    elevation: 10,
  },
  accentBadge: {
    position: 'absolute',
    top: -12,
    right: -12,
    width: 48,
    height: 48,
    borderRadius: 24,
    borderWidth: 4,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
  },
  textContainer: {
    alignItems: 'center',
    gap: 8,
  },
  title: {
    fontSize: 40,
    fontWeight: '800',
    letterSpacing: -1,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 18,
    fontWeight: '600',
    textAlign: 'center',
  },
  loaderArea: {
    alignItems: 'center',
    gap: 16,
    width: '100%',
    paddingBottom: 32,
  },
  progressBarContainer: {
    height: 6,
    width: 128,
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressBar: {
    height: '100%',
    backgroundColor: '#13ec5b',
    borderRadius: 3,
  },
  loadingText: {
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 2,
  },
});
