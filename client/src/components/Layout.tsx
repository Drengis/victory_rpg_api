import React from 'react';
import { useAuthStore } from '../store/authStore';
import { Shield, LogOut, User as UserIcon, Users, Star, Sword, ShoppingBag, Briefcase } from 'lucide-react';
import { Link } from 'react-router-dom';

interface LayoutProps {
    children: React.ReactNode;
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
    const { user, logout, isAuthenticated } = useAuthStore();

    return (
        <div className="min-h-screen w-full bg-slate-950 text-slate-200">
            <header className="border-b border-slate-800 bg-slate-900/50 backdrop-blur-md sticky top-0 z-50">
                <div className="container mx-auto px-4 h-16 flex items-center justify-between">
                    <Link to="/" className="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <Shield className="w-8 h-8 text-amber-500" />
                        <h1 className="text-xl font-bold tracking-tight text-slate-100">Victory RPG</h1>
                    </Link>

                    <nav className="flex items-center gap-6">
                        {isAuthenticated ? (
                            <div className="flex items-center gap-6">
                                <Link
                                    to="/dashboard"
                                    className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-amber-500 transition-colors"
                                >
                                    <Shield className="w-4 h-4" />
                                    <span>Замок</span>
                                </Link>
                                <Link
                                    to="/abilities"
                                    className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-amber-500 transition-colors"
                                >
                                    <Star className="w-4 h-4" />
                                    <span>Навыки</span>
                                </Link>
                                <Link
                                    to="/inventory"
                                    className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-amber-500 transition-colors"
                                >
                                    <Briefcase className="w-4 h-4" />
                                    <span>Сумка</span>
                                </Link>
                                <Link
                                    to="/shops"
                                    className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-amber-500 transition-colors"
                                >
                                    <ShoppingBag className="w-4 h-4" />
                                    <span>Рынок</span>
                                </Link>
                                <Link
                                    to="/characters"
                                    className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-amber-500 transition-colors"
                                >
                                    <Users className="w-4 h-4" />
                                    <span>Герои</span>
                                </Link>
                                <div className="flex items-center gap-2 text-sm font-medium text-slate-300">
                                    <UserIcon className="w-4 h-4" />
                                    <span>{user?.name}</span>
                                </div>
                                <button
                                    onClick={logout}
                                    className="flex items-center gap-2 text-sm font-medium text-red-400 hover:text-red-300 transition-colors"
                                >
                                    <LogOut className="w-4 h-4" />
                                    <span>Выход</span>
                                </button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-4">
                                <Link to="/login" className="text-sm font-medium text-slate-300 hover:text-white transition-colors">
                                    Вход
                                </Link>
                                <Link
                                    to="/register"
                                    className="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-bold hover:bg-amber-500 transition-all shadow-lg shadow-amber-900/20"
                                >
                                    Играть бесплатно
                                </Link>
                            </div>
                        )}
                    </nav>
                </div>
            </header>

            <main className="container mx-auto px-4 py-8">
                {children}
            </main>

            <footer className="border-t border-slate-800 py-8 mt-auto">
                <div className="container mx-auto px-4 text-center text-slate-500 text-sm">
                    &copy; {new Date().getFullYear()} Victory RPG. Все права защищены.
                </div>
            </footer>
        </div>
    );
};

export default Layout;
