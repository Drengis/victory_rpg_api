import axios from './axios';
import type { Shop } from '../types/game';

export const getShops = async (): Promise<Shop[]> => {
    const response = await axios.get('/shops');
    return response.data;
};

export const getShop = async (shopId: number): Promise<Shop> => {
    const response = await axios.get(`/shops/${shopId}`);
    return response.data;
};

export interface BuyParams {
    character_id: number;
    item_id: number;
    quantity: number;
}

export const buyItem = async (shopId: number, params: BuyParams) => {
    const response = await axios.post(`/shops/${shopId}/buy`, params);
    return response.data;
};

export const sellItem = async (characterItemId: number, quantity: number = 1) => {
    const response = await axios.post('/inventory/sell', {
        character_item_id: characterItemId,
        quantity
    });
    return response.data;
};
