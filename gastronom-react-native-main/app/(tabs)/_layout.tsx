
import { Tabs } from 'expo-router';
import React from 'react';

import { IconSymbol } from '@/components/ui/icon-symbol';
import { View } from 'react-native';

export default function TabLayout() {
  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: '#13ec5b',
        headerShown: false,
        tabBarStyle: {
            height: 72,
            paddingBottom: 10,
            paddingTop: 10,
        }
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Главная',
          tabBarIcon: ({ color, focused }) => (
            <View style={{ alignItems: 'center', gap: 4 }}>
                <IconSymbol name="house.fill" size={24} color={focused ? '#13ec5b' : color} />
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="search"
        options={{
          title: 'Поиск',
          tabBarIcon: ({ color, focused }) => (
            <View style={{ alignItems: 'center', gap: 4 }}>
                <IconSymbol name="magnifyingglass" size={24} color={focused ? '#13ec5b' : color} />
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="cart"
        options={{
          title: 'Корзина',
          tabBarIcon: ({ color, focused }) => (
            <View style={{ alignItems: 'center', gap: 4 }}>
                <IconSymbol name="cart.fill" size={24} color={focused ? '#13ec5b' : color} />
            </View>
          ),
        }}
      />
        <Tabs.Screen
        name="profile"
        options={{
          title: 'Профиль',
          tabBarIcon: ({ color, focused }) => (
            <View style={{ alignItems: 'center', gap: 4 }}>
                <IconSymbol name="person.fill" size={24} color={focused ? '#13ec5b' : color} />
            </View>
          ),
        }}
      />
      <Tabs.Screen
        name="orders"
        options={{
          href: null,
        }}
      />
      <Tabs.Screen
        name="explore"
        options={{
          href: null,
        }}
      />
    </Tabs>
  );
}
