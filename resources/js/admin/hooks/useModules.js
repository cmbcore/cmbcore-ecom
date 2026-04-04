import { useContext } from 'react';
import { ModuleContext } from '../contexts/ModuleContext';

export function useModules() {
    return useContext(ModuleContext);
}