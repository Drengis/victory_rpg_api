import React, { useEffect, useState } from 'react';
import { getShops } from '../api/shopApi';
import type { Shop } from '../types/game';
import ShopModal from '../components/shop/ShopModal';
import { Store, Loader2, ArrowRight, ArrowLeft } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

const ShopsPage: React.FC = () => {
    const navigate = useNavigate();
    const [shops, setShops] = useState<Shop[]>([]);
    const [loading, setLoading] = useState(true);
    const [selectedShop, setSelectedShop] = useState<Shop | null>(null);

    useEffect(() => {
        const fetchShops = async () => {
            try {
                const data = await getShops();
                setShops(data);
            } catch (error) {
                console.error('Failed to fetch shops', error);
            } finally {
                setLoading(false);
            }
        };
        fetchShops();
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[50vh]">
                <Loader2 className="w-8 h-8 animate-spin text-amber-500" />
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto space-y-8">
            <div className="flex items-center gap-4">
                <button
                    onClick={() => navigate('/dashboard')}
                    className="p-2 hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-white"
                    title="Вернуться в хаб"
                >
                    <ArrowLeft className="w-6 h-6" />
                </button>
                <div className="flex items-center gap-3">
                    <Store className="w-8 h-8 text-amber-500" />
                    <h2 className="text-3xl font-bold text-white">Торговые лавки</h2>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {shops.map(shop => (
                    <div
                        key={shop.id}
                        className="bg-slate-900 border border-slate-800 rounded-xl p-6 hover:border-amber-500/50 transition-all cursor-pointer group"
                        onClick={() => setSelectedShop(shop)}
                    >
                        <h3 className="text-xl font-bold text-slate-100 group-hover:text-amber-500 transition-colors">
                            {shop.name}
                        </h3>
                        <p className="text-slate-400 mt-2 text-sm leading-relaxed">
                            {shop.description}
                        </p>
                        <div className="mt-4 flex items-center text-amber-500 text-sm font-bold gap-1">
                            Войти в магазин <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </div>
                    </div>
                ))}
            </div>

            {selectedShop && (
                <ShopModal
                    shop={selectedShop}
                    onClose={() => setSelectedShop(null)}
                />
            )}
        </div>
    );
};

export default ShopsPage;
