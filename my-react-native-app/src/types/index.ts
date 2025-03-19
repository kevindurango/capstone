// This file exports TypeScript types and interfaces used throughout the application.

export interface Item {
    id: string;
    title: string;
    description: string;
}

export interface NavigationProps {
    navigation: {
        navigate: (screen: string, params?: object) => void;
    };
}

export interface HomeScreenProps {
    items: Item[];
}

export interface DetailsScreenProps {
    item: Item;
}