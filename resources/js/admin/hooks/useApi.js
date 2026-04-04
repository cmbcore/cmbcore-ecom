import { useCallback, useState } from 'react';
import api from '../services/api';

export function useApi() {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const request = useCallback(async (method, url, config = {}) => {
        setLoading(true);
        setError(null);

        try {
            const response = await api.request({ method, url, ...config });
            return response.data;
        } catch (nextError) {
            setError(nextError);
            throw nextError;
        } finally {
            setLoading(false);
        }
    }, []);

    return { loading, error, request };
}