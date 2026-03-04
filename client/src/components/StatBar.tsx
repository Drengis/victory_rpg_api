import React from 'react';
import { cn } from '../lib/utils';

interface StatBarProps {
    current: number;
    max: number;
    label: string;
    color: 'red' | 'blue' | 'green' | 'amber';
    icon?: React.ReactNode;
}

const StatBar: React.FC<StatBarProps> = ({ current, max, label, color, icon }) => {
    const percentage = Math.min(100, Math.max(0, (current / max) * 100));

    const colors = {
        red: 'bg-red-600',
        blue: 'bg-blue-600',
        green: 'bg-green-600',
        amber: 'bg-amber-600',
    };

    const bgColors = {
        red: 'bg-red-950/50',
        blue: 'bg-blue-950/50',
        green: 'bg-green-950/50',
        amber: 'bg-amber-950/50',
    };

    return (
        <div className="space-y-1.5 w-full">
            <div className="flex justify-between items-end px-1">
                <div className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400">
                    {icon}
                    {label}
                </div>
                <div className="text-xs font-mono text-slate-200">
                    {Math.floor(current)} / {Math.round(max)}
                </div>
            </div>
            <div className={cn("h-3 w-full rounded-full overflow-hidden border border-slate-800 shadow-inner", bgColors[color])}>
                <div
                    className={cn("h-full transition-all duration-500 ease-out rounded-full", colors[color])}
                    style={{ width: `${percentage}%` }}
                >
                    <div className="w-full h-full bg-gradient-to-t from-black/20 to-white/20" />
                </div>
            </div>
        </div>
    );
};

export default StatBar;
