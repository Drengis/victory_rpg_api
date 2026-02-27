import React, { useEffect, useState } from 'react';
import { getShop, buyItem } from '../../api/shopApi';
import type { Shop, ShopItem } from '../../types/game';
import { X, Loader2, Coins, ShoppingCart } from 'lucide-react';
import { useGameStore } from '../../store/gameStore';
import api from '../../api/axios';
import ItemCard from '../ItemCard';

interface ShopModalProps {
    shop: Shop;
    onClose: () => void;
}

const ShopModal: React.FC<ShopModalProps> = ({ shop, onClose }) => {
    const [fullShop, setFullShop] = useState<Shop | null>(null);
    const [loading, setLoading] = useState(true);
    const [buying, setBuying] = useState<number | null>(null);
    const { currentCharacter, setCurrentCharacter } = useGameStore();

    useEffect(() => {
        const fetchShopDetails = async () => {
            try {
                const data = await getShop(shop.id);
                setFullShop(data);
            } catch (error) {
                console.error('Failed to fetch shop details', error);
            } finally {
                setLoading(false);
            }
        };
        fetchShopDetails();
    }, [shop.id]);

    const handleBuy = async (item: ShopItem) => {
        if (!currentCharacter) return;

        setBuying(item.id);
        try {
            await buyItem(shop.id, {
                character_id: currentCharacter.id,
                item_id: item.id,
                quantity: 1
            });
            // Обновляем данные персонажа (золото, инвентарь)
            const response = await api.get(`/characters/${currentCharacter.id}`);
            if (response.data.data) {
                setCurrentCharacter(response.data.data);
            }
        } catch (error: any) {
            alert(error.response?.data?.error || 'Ошибка при покупке');
        } finally {
            setBuying(null);
        }
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div className="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl">
                {/* Header */}
                <div className="p-6 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-100">{shop.name}</h2>
                        <p className="text-sm text-slate-400 mt-1">{shop.description}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 hover:bg-slate-800 rounded-full transition-colors text-slate-400 hover:text-white"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {loading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="w-8 h-8 animate-spin text-amber-500" />
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {(fullShop?.items as unknown as ShopItem[])?.map(item => {
                                const price = item.pivot.price_override || item.base_price; // Тут можно добавить calculatePrice на клиенте, но лучше брать с бэка
                                const canAfford = currentCharacter && currentCharacter.gold >= price;

                                return (
                                    <div key={item.id} className="bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 flex flex-col gap-4">
                                        <ItemCard item={item} ilevel={item.pivot.ilevel} hideActions playerClass={currentCharacter?.class} />

                                        <div className="mt-auto flex items-center justify-between pt-4 border-t border-slate-700/50">
                                            <div className="flex items-center gap-1.5 text-amber-500 font-bold">
                                                <Coins className="w-4 h-4" />
                                                <span>{price}</span>
                                            </div>

                                            <button
                                                onClick={() => handleBuy(item)}
                                                disabled={!canAfford || buying === item.id}
                                                className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all ${canAfford
                                                    ? 'bg-amber-600 text-white hover:bg-amber-500 shadow-lg shadow-amber-900/20'
                                                    : 'bg-slate-700 text-slate-400 cursor-not-allowed'
                                                    }`}
                                            >
                                                {buying === item.id ? (
                                                    <Loader2 className="w-4 h-4 animate-spin" />
                                                ) : (
                                                    <ShoppingCart className="w-4 h-4" />
                                                )}
                                                <span>{buying === item.id ? 'Покупка...' : 'Купить'}</span>
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Footer / Stats */}
                <div className="p-4 bg-slate-950/50 border-t border-slate-800 flex items-center justify-end gap-6 text-sm">
                    <div className="flex items-center gap-2 text-slate-400">
                        Ваше золото:
                        <span className="text-amber-500 font-bold flex items-center gap-1">
                            <Coins className="w-4 h-4" />
                            {currentCharacter?.gold || 0}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ShopModal;
