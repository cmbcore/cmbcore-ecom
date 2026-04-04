import { useContext } from 'react';
import { LocalizationContext } from '../contexts/LocalizationContext';

export function useLocale() {
    return useContext(LocalizationContext);
}
