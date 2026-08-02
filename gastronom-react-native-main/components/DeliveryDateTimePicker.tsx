import React from 'react';
import { StyleSheet, View, Text, TouchableOpacity, ScrollView } from 'react-native';

interface DeliveryPickerProps {
  colors: any;
  selectedDate: Date;
  onDateChange: (date: Date) => void;
  selectedTime: string;
  onTimeChange: (time: string) => void;
}

export default function DeliveryDateTimePicker({
  colors,
  selectedDate,
  onDateChange,
  selectedTime,
  onTimeChange,
}: DeliveryPickerProps) {

  const days = React.useMemo(() => {
    const list = [];
    const locale = 'ru-RU';
    
    for (let i = 0; i < 7; i++) {
      const d = new Date();
      d.setDate(d.getDate() + i);
      
      let label = d.toLocaleDateString(locale, { weekday: 'short' });
      label = label.charAt(0).toUpperCase() + label.slice(1);

      if (i === 0) label = 'СЕГОДНЯ';
      if (i === 1) label = 'ЗАВТРА';

      list.push({
        id: d.toISOString().split('T')[0],
        dateObj: d,
        label: label,
        dayNum: d.getDate(),
        month: d.toLocaleDateString(locale, { month: 'short' }).replace('.', ''),
      });
    }
    return list;
  }, []);

  const timeSlots = ['09:00 - 13:00', '13:00 - 17:00', '17:00 - 21:00'];

  const isSameDay = (date1: Date, date2: Date) => {
    return date1.getDate() === date2.getDate() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getFullYear() === date2.getFullYear();
  };

  return (
    <View style={styles.section}>
      <View style={styles.sectionHeader}>
        <Text style={[styles.sectionTitle, { color: colors.text }]}>Когда доставить?</Text>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dateScroll}>
        {days.map((day) => {
          const active = isSameDay(selectedDate, day.dateObj);
          return (
            <TouchableOpacity 
              key={day.id} 
              onPress={() => onDateChange(day.dateObj)}
              style={[
                styles.dateCard, 
                active 
                  ? [styles.dateCardActive, { borderColor: colors.primary, backgroundColor: colors.primary }] 
                  : { backgroundColor: colors.surface, borderColor: colors.border }
              ]}
            >
              <Text style={[styles.dateLabel, { color: active ? '#102216' : colors.textSub }]}>{day.label}</Text>
              <Text style={[styles.dateValue, { color: active ? '#102216' : colors.text }]}>{day.dayNum}</Text>
              <Text style={[styles.dateMonth, { color: active ? '#102216' : colors.textSub }]}>{day.month}</Text>
            </TouchableOpacity>
          );
        })}
      </ScrollView>

      <Text style={[styles.inputLabel, { color: colors.textSub, marginTop: 16, marginBottom: 8 }]}>Удобный временной интервал:</Text>
      <View style={styles.timeGrid}>
        {timeSlots.map((time) => {
          const active = selectedTime === time;
          return (
            <TouchableOpacity
              key={time}
              onPress={() => onTimeChange(time)}
              style={[
                styles.timeButton,
                {
                  backgroundColor: active ? 'rgba(19, 236, 91, 0.15)' : colors.surface,
                  borderColor: active ? colors.primary : colors.border,
                  borderWidth: active ? 2 : 1,
                }
              ]}
            >
              <Text style={[styles.timeText, { color: active ? colors.primaryDark : colors.text, fontWeight: active ? '700' : '500' }]}>
                {time}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  section: {
    marginBottom: 24,
  },
  sectionHeader: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
  },
  dateScroll: {
    gap: 12,
    paddingRight: 16,
  },
  dateCard: {
    width: 88,
    height: 96,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dateCardActive: {
    borderWidth: 2,
  },
  dateLabel: {
    fontSize: 10,
    fontWeight: '700',
    marginBottom: 2,
  },
  dateValue: {
    fontSize: 24,
    fontWeight: '800',
  },
  dateMonth: {
    fontSize: 12,
    fontWeight: '700',
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  timeGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  timeButton: {
    flex: 1,
    minWidth: '45%',
    height: 48,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  timeText: {
    fontSize: 14,
  },
});