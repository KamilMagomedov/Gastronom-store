
import React, { useState } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, TextInput } from 'react-native';
import { Stack, useRouter, useLocalSearchParams } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const CANCEL_REASONS = [
  'Передумал(а) покупать',
  'Долгое ожидание',
  'Заказ сделан случайно',
  'Другая причина'
];

export default function CancelOrderScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [selectedReason, setSelectedReason] = useState(CANCEL_REASONS[0]);
  const [comment, setComment] = useState('');

  const handleCancel = () => {
    // Here you would typically call an API to cancel the order
    router.back();
  };

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
            style={styles.backButton}
            onPress={() => router.back()}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Отмена заказа</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.content}>
          <View style={[styles.orderInfoCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.orderIcon, { backgroundColor: colorScheme === 'dark' ? '#102216' : '#f0f4f2' }]}>
              <IconSymbol name="receipt" size={24} color={colorScheme === 'dark' ? '#13ec5b' : '#111813'} />
            </View>
            <View style={styles.orderInfo}>
              <ThemedText style={styles.orderNumber}>Заказ #{id || '3920'}</ThemedText>
              <ThemedText style={[styles.orderStatus, { color: colors.textSub }]}>В обработке • Доставка из ВкусВилл</ThemedText>
            </View>
          </View>

          <ThemedText style={styles.sectionTitle}>Почему вы хотите отменить заказ?</ThemedText>
          
          <View style={styles.reasonsList}>
            {CANCEL_REASONS.map((reason) => (
              <TouchableOpacity 
                key={reason}
                style={[
                  styles.reasonItem, 
                  { 
                    backgroundColor: colors.surface, 
                    borderColor: selectedReason === reason ? '#13ec5b' : colors.border 
                  }
                ]}
                onPress={() => setSelectedReason(reason)}
              >
                <View style={[
                  styles.radioButton, 
                  { borderColor: selectedReason === reason ? '#13ec5b' : colors.border }
                ]}>
                  {selectedReason === reason && <View style={styles.radioButtonInner} />}
                </View>
                <ThemedText style={[
                  styles.reasonText,
                  selectedReason === reason && { color: '#13ec5b' }
                ]}>
                  {reason}
                </ThemedText>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.commentSection}>
            <ThemedText style={styles.commentLabel}>Комментарий</ThemedText>
            <TextInput
              style={[
                styles.commentInput, 
                { 
                  backgroundColor: colors.surface, 
                  borderColor: colors.border,
                  color: colors.text
                }
              ]}
              placeholder="Опишите причину (необязательно)..."
              placeholderTextColor={colors.textSub}
              multiline
              numberOfLines={4}
              value={comment}
              onChangeText={setComment}
            />
          </View>
        </View>
      </ScrollView>

      <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 20), borderTopColor: colors.border, backgroundColor: colors.background }]}>
        <TouchableOpacity 
          style={styles.confirmButton}
          onPress={handleCancel}
        >
          <ThemedText style={styles.confirmButtonText}>Подтвердить отмену</ThemedText>
        </TouchableOpacity>
      </View>
    </ThemedView>
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
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    flex: 1,
    textAlign: 'center',
  },
  scrollContent: {
    paddingBottom: 120,
  },
  content: {
    padding: 16,
  },
  orderInfoCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
  },
  orderIcon: {
    width: 48,
    height: 48,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderInfo: {
    marginLeft: 16,
  },
  orderNumber: {
    fontSize: 16,
    fontWeight: '700',
  },
  orderStatus: {
    fontSize: 14,
    marginTop: 2,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 16,
  },
  reasonsList: {
    gap: 12,
  },
  reasonItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
  },
  radioButton: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  radioButtonInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#13ec5b',
  },
  reasonText: {
    fontSize: 14,
    fontWeight: '500',
  },
  commentSection: {
    marginTop: 24,
  },
  commentLabel: {
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 8,
    marginLeft: 4,
  },
  commentInput: {
    borderRadius: 16,
    borderWidth: 1,
    padding: 16,
    fontSize: 16,
    textAlignVertical: 'top',
    minHeight: 140,
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    borderTopWidth: 1,
  },
  confirmButton: {
    backgroundColor: '#dc2626',
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#dc2626',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  confirmButtonText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '700',
  },
});
