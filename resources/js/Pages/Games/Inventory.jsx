import { useState, useRef, useCallback } from 'react';
import {
    DndContext,
    closestCenter,
    PointerSensor,
    TouchSensor,
    useSensor,
    useSensors,
    DragOverlay,
    useDraggable,
    useDroppable,
} from '@dnd-kit/core';
import BottomNav from './BottomNav';

const EQUIPMENT_SLOTS = [
    { id: 'head', label: 'Head', icon: 'fa-helmet-safety', position: 'top' },
    { id: 'weapon', label: 'Weapon', icon: 'fa-sword', position: 'left-top', equipped: true, name: 'Sun-Eater' },
    { id: 'shield', label: 'Off-hand', icon: 'fa-shield-halved', position: 'left-bottom', equipped: true },
    { id: 'chest', label: 'Chest', icon: 'fa-shirt', position: 'right-top' },
    { id: 'accessory', label: 'Ring', icon: 'fa-ring', position: 'right-bottom', equipped: true, name: 'Ember Ring' },
    { id: 'legs', label: 'Legs', icon: 'fa-shoe-prints', position: 'bottom' },
];

const INITIAL_ITEMS = [
    { id: 1, icon: 'fa-flask', color: 'text-[#efc84e]', name: 'Health Potion', description: 'Restores a moderate amount of health when consumed.', rarity: 'Common', type: 'Consumable' },
    { id: 2, icon: 'fa-wand-sparkles', color: 'text-[#efc84e]', name: 'Wand of Fire', description: 'A charred wand that crackles with dormant flame. Grants minor fire magic.', rarity: 'Uncommon', type: 'Weapon' },
    { id: 3, icon: 'fa-flask-vial', color: 'text-[#ffb4a8]', name: 'Elixir', description: 'A shimmering elixir that mends wounds and clears the mind.', rarity: 'Rare', type: 'Consumable', count: 12 },
    { id: 4, icon: 'fa-book', color: 'text-[#efc84e]/60', name: 'Old Tome', description: 'Pages crumble at the edges. Contains forgotten lore about the ancient world.', rarity: 'Common', type: 'Quest Item' },
    { id: 5, icon: 'fa-key', color: 'text-[#efc84e]/60', name: 'Rusty Key', description: 'A key caked in rust. It might still open something somewhere.', rarity: 'Common', type: 'Key Item' },
    { id: 6, icon: 'fa-scroll', color: 'text-[#efc84e]', name: 'Ancient Scroll', description: 'Sealed with wax bearing an unknown sigil. Radiates faint magic.', rarity: 'Uncommon', type: 'Quest Item' },
    { id: 7, icon: 'fa-gem', color: 'text-[#ffb4a8]', name: 'Ruby', description: 'A blood-red gemstone that pulses with inner warmth.', rarity: 'Rare', type: 'Material' },
    { id: 8, icon: 'fa-coins', color: 'text-[#efc84e]', name: 'Gold Pouch', description: 'A heavy leather pouch clinking with coins.', rarity: 'Common', type: 'Currency' },
];

const RARITY_COLORS = {
    Common: 'text-[#a38d7a]',
    Uncommon: 'text-[#4ade80]',
    Rare: 'text-[#ffb4a8]',
    Legendary: 'text-[#efc84e]',
};

// --- Droppable Slot ---
function DroppableSlot({ slotIndex, children }) {
    const { isOver, setNodeRef } = useDroppable({ id: `slot-${slotIndex}` });

    return (
        <div
            ref={setNodeRef}
            className={`aspect-square rounded-lg transition-colors ${
                children
                    ? ''
                    : isOver
                        ? 'bg-[#34343a]/60 border-2 border-dashed border-[#efc84e]/40'
                        : 'bg-[#34343a]/20 border border-[#554434]/10'
            }`}
        >
            {children}
        </div>
    );
}

// --- Draggable Item ---
function DraggableItem({ item, onTap, onDoubleTap }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        isDragging,
    } = useDraggable({ id: item.id });

    const style = transform
        ? { transform: `translate(${transform.x}px, ${transform.y}px)` }
        : undefined;

    const tapTimer = useRef(null);
    const tapCount = useRef(0);
    const didDrag = useRef(false);

    const handlePointerDown = useCallback(() => {
        didDrag.current = false;
    }, []);

    const handlePointerUp = useCallback((e) => {
        if (isDragging || didDrag.current) return;

        tapCount.current += 1;

        if (tapCount.current === 1) {
            tapTimer.current = setTimeout(() => {
                if (tapCount.current === 1) {
                    onTap(item);
                }
                tapCount.current = 0;
            }, 250);
        } else if (tapCount.current === 2) {
            clearTimeout(tapTimer.current);
            tapCount.current = 0;
            onDoubleTap(item, e);
        }
    }, [item, onTap, onDoubleTap, isDragging]);

    // Track if a drag actually happened
    if (isDragging) {
        didDrag.current = true;
    }

    return (
        <div
            ref={setNodeRef}
            style={style}
            {...attributes}
            {...listeners}
            onPointerDown={handlePointerDown}
            onPointerUp={handlePointerUp}
            className={`aspect-square bg-[#34343a]/40 border border-[#554434]/20 rounded-lg flex items-center justify-center hover:bg-[#34343a] transition-colors cursor-pointer relative select-none touch-none ${
                isDragging ? 'opacity-30 z-50' : ''
            }`}
        >
            <i className={`fa-solid ${item.icon} ${item.color}`} />
            {item.count && (
                <span className="absolute bottom-1 right-1.5 text-[8px] font-bold text-[#e3e1e9]/50">
                    x{item.count}
                </span>
            )}
        </div>
    );
}

// --- Drag Overlay Item ---
function DragOverlayItem({ item }) {
    return (
        <div className="aspect-square w-16 bg-[#34343a] border-2 border-[#efc84e]/60 rounded-lg flex items-center justify-center shadow-[0_0_20px_rgba(239,200,78,0.3)] select-none">
            <i className={`fa-solid ${item.icon} ${item.color}`} />
            {item.count && (
                <span className="absolute bottom-1 right-1.5 text-[8px] font-bold text-[#e3e1e9]/50">
                    x{item.count}
                </span>
            )}
        </div>
    );
}

// --- Item Drawer ---
function ItemDrawer({ item, onClose }) {
    if (!item) return null;

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/60 z-40 transition-opacity"
                onClick={onClose}
            />
            {/* Drawer */}
            <div className="fixed bottom-0 left-0 right-0 z-50 bg-[#1a1b21] border-t border-[#554434]/30 rounded-t-2xl shadow-[0_-10px_40px_rgba(0,0,0,0.8)] animate-slide-up">
                {/* Handle */}
                <div className="flex justify-center pt-3 pb-2">
                    <div className="w-10 h-1 bg-[#554434]/50 rounded-full" />
                </div>

                <div className="px-6 pb-8">
                    {/* Header */}
                    <div className="flex items-start gap-4 mb-4">
                        <div className="w-14 h-14 bg-[#34343a]/60 border border-[#554434]/30 rounded-lg flex items-center justify-center shrink-0">
                            <i className={`fa-solid ${item.icon} ${item.color} text-xl`} />
                        </div>
                        <div className="flex-1 min-w-0">
                            <h3 className="font-narration text-xl text-[#e3e1e9] italic">{item.name}</h3>
                            <div className="flex items-center gap-3 mt-1">
                                <span className={`text-[10px] font-bold uppercase tracking-widest ${RARITY_COLORS[item.rarity] || 'text-[#a38d7a]'}`}>
                                    {item.rarity}
                                </span>
                                <span className="text-[10px] uppercase tracking-widest text-[#a38d7a]">
                                    {item.type}
                                </span>
                            </div>
                        </div>
                        {item.count && (
                            <span className="text-sm text-[#a38d7a] font-mono">x{item.count}</span>
                        )}
                    </div>

                    {/* Description */}
                    <p className="font-narration text-sm text-[#dbc2ad] leading-relaxed italic mb-6">
                        "{item.description}"
                    </p>

                    {/* Divider */}
                    <div className="h-px bg-gradient-to-r from-transparent via-[#554434] to-transparent mb-4" />

                    {/* Actions */}
                    <div className="flex gap-3">
                        <button
                            onClick={onClose}
                            className="flex-1 py-3 bg-[#292a2f] border border-[#554434]/30 rounded-lg text-[#e3e1e9] text-xs font-bold uppercase tracking-widest hover:bg-[#34343a] transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

// --- Context Menu ---
function ContextMenu({ item, position, onRemove, onShowMore, onClose }) {
    if (!item) return null;

    return (
        <>
            <div className="fixed inset-0 z-40" onClick={onClose} />
            <div
                className="fixed z-50 bg-[#292a2f] border border-[#554434]/40 rounded-lg shadow-[0_4px_20px_rgba(0,0,0,0.6)] overflow-hidden min-w-[160px] animate-fade-in"
                style={{
                    left: Math.min(position.x, window.innerWidth - 180),
                    top: Math.min(position.y, window.innerHeight - 120),
                }}
            >
                <div className="px-3 py-2 border-b border-[#554434]/20">
                    <span className="text-[10px] font-bold uppercase tracking-widest text-[#a38d7a]">{item.name}</span>
                </div>
                <button
                    onClick={() => { onShowMore(item); onClose(); }}
                    className="w-full px-4 py-3 text-left text-sm text-[#e3e1e9] hover:bg-[#34343a] transition-colors flex items-center gap-3"
                >
                    <i className="fa-solid fa-circle-info text-[#efc84e]/60 text-xs" />
                    Show more
                </button>
                <button
                    onClick={() => { onRemove(item.id); onClose(); }}
                    className="w-full px-4 py-3 text-left text-sm text-[#ffb4ab] hover:bg-[#93000a]/20 transition-colors flex items-center gap-3"
                >
                    <i className="fa-solid fa-trash text-[#ffb4ab]/60 text-xs" />
                    Remove
                </button>
            </div>
        </>
    );
}

// --- Equipment Slot ---
function EquipmentSlot({ slot }) {
    const isEquipped = slot.equipped;
    const baseClasses = "backdrop-blur-md border rounded-lg flex items-center justify-center transition-colors cursor-pointer shadow-lg";

    if (slot.id === 'weapon' && isEquipped) {
        return (
            <div className="relative group">
                <div className={`w-[72px] h-[72px] bg-[#d2ad35]/20 ${baseClasses} border-[#efc84e]/60 shadow-[0_0_20px_rgba(239,200,78,0.2)]`}>
                    <i className={`fa-solid ${slot.icon} text-[#efc84e] text-xl`} />
                </div>
                {slot.name && (
                    <span className="absolute -bottom-5 left-1/2 -translate-x-1/2 font-narration text-[10px] uppercase tracking-wider text-[#efc84e] font-bold whitespace-nowrap">
                        {slot.name}
                    </span>
                )}
            </div>
        );
    }

    return (
        <div className="relative group">
            <div className={`w-16 h-16 bg-[#292a2f]/60 ${baseClasses} border-[#efc84e]/20 hover:border-[#efc84e]`}>
                <i className={`fa-solid ${slot.icon} ${isEquipped ? 'text-[#efc84e]' : 'text-[#efc84e]/30 group-hover:text-[#efc84e]'}`} />
            </div>
            {slot.name && (
                <span className="absolute -bottom-5 left-1/2 -translate-x-1/2 font-narration text-[10px] uppercase tracking-wider text-[#efc84e]/60 whitespace-nowrap">
                    {slot.name}
                </span>
            )}
        </div>
    );
}

// --- Stats Ribbon ---
function StatsRibbon({ stats }) {
    const statList = [
        { key: 'str', label: 'STR' },
        { key: 'dex', label: 'DEX' },
        { key: 'con', label: 'CON' },
        { key: 'int', label: 'INT' },
        { key: 'wis', label: 'WIS' },
        { key: 'cha', label: 'CHA' },
    ];

    return (
        <div className="grid grid-cols-6 gap-px bg-[#1a1b21] p-px rounded-lg border border-[#554434]/10">
            {statList.map(({ key, label }) => (
                <div key={key} className="bg-[#121318] py-3 flex flex-col items-center justify-center">
                    <span className="font-sans text-[9px] uppercase text-[#dbc2ad] tracking-widest">{label}</span>
                    <span className="font-narration text-xl text-[#efc84e]">{stats[key] ?? '—'}</span>
                </div>
            ))}
        </div>
    );
}

// --- Inventory Grid with free placement DnD ---
const TOTAL_SLOTS = 20;

function InventoryGrid({ slots, setSlots, onTapItem, onDoubleTapItem }) {
    const [activeId, setActiveId] = useState(null);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { delay: 200, tolerance: 5 },
        }),
        useSensor(TouchSensor, {
            activationConstraint: { delay: 200, tolerance: 5 },
        }),
    );

    const activeItem = activeId
        ? slots.find(s => s && s.id === activeId)
        : null;

    function handleDragStart(event) {
        setActiveId(event.active.id);
    }

    function handleDragEnd(event) {
        setActiveId(null);
        const { active, over } = event;
        if (!over) return;

        const targetSlotIndex = parseInt(over.id.replace('slot-', ''), 10);
        const sourceSlotIndex = slots.findIndex(s => s && s.id === active.id);

        if (sourceSlotIndex === targetSlotIndex) return;

        setSlots(prev => {
            const next = [...prev];
            const sourceItem = next[sourceSlotIndex];
            const targetItem = next[targetSlotIndex];
            // Swap (works for empty targets too since targetItem is null)
            next[targetSlotIndex] = sourceItem;
            next[sourceSlotIndex] = targetItem;
            return next;
        });
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
        >
            <div className="grid grid-cols-5 gap-2.5">
                {slots.map((item, index) => (
                    <DroppableSlot key={index} slotIndex={index}>
                        {item && (
                            <DraggableItem
                                item={item}
                                onTap={onTapItem}
                                onDoubleTap={onDoubleTapItem}
                            />
                        )}
                    </DroppableSlot>
                ))}
            </div>

            <DragOverlay>
                {activeItem ? <DragOverlayItem item={activeItem} /> : null}
            </DragOverlay>
        </DndContext>
    );
}

// --- Main Page ---
export default function Inventory({ game }) {
    const [activeTab, setActiveTab] = useState('equipment');
    const [slots, setSlots] = useState(() => {
        const s = new Array(TOTAL_SLOTS).fill(null);
        INITIAL_ITEMS.forEach((item, i) => { s[i] = item; });
        return s;
    });
    const [drawerItem, setDrawerItem] = useState(null);
    const [contextMenu, setContextMenu] = useState(null);

    const character = game.characters?.[0] ?? {};
    const stats = character.stats ?? { str: 14, dex: 12, con: 16, int: 10, wis: 13, cha: 8 };

    const handleTapItem = useCallback((item) => {
        setDrawerItem(item);
    }, []);

    const handleDoubleTapItem = useCallback((item, e) => {
        const rect = e.target.getBoundingClientRect();
        setContextMenu({
            item,
            position: { x: rect.left, y: rect.top - 10 },
        });
    }, []);

    const handleRemoveItem = useCallback((id) => {
        setSlots(prev => prev.map(s => (s && s.id === id) ? null : s));
    }, []);

    return (
        <div className="h-screen bg-[#121318] text-[#e3e1e9] font-sans overflow-hidden flex flex-col">
            {/* Tab Content - takes full remaining height */}
            <div className="flex-1 min-h-0 overflow-y-auto pb-24">
                {activeTab === 'equipment' ? (
                    <div className="flex flex-col h-full">
                        {/* Character Portrait with Equipment Slots */}
                        <div className="relative flex-1 min-h-0 flex items-center justify-center">
                            {/* Portrait */}
                            <div className="absolute inset-0 flex items-center justify-center p-10 pt-6 pb-2">
                                <div className="relative w-full h-full max-w-xs bg-[#0d0e13] rounded-lg overflow-hidden border border-[#554434]/20 shadow-2xl">
                                    <div className="w-full h-full bg-[#1a1b21] flex items-center justify-center">
                                        <i className="fa-solid fa-user text-[#554434]/30 text-6xl" />
                                    </div>
                                    <div className="absolute inset-0 bg-gradient-to-t from-[#121318] via-transparent to-transparent" />
                                    <div className="absolute inset-0 shadow-[inset_0_0_80px_rgba(239,200,78,0.1)] pointer-events-none" />
                                </div>
                            </div>

                            {/* Head - top center */}
                            <div className="absolute top-3 left-1/2 -translate-x-1/2 z-10">
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[0]} />
                            </div>

                            {/* Left column */}
                            <div className="absolute left-3 top-1/2 -translate-y-1/2 flex flex-col gap-10 z-10">
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[1]} />
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[2]} />
                            </div>

                            {/* Right column */}
                            <div className="absolute right-3 top-1/2 -translate-y-1/2 flex flex-col gap-10 z-10">
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[3]} />
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[4]} />
                            </div>

                            {/* Legs - bottom center */}
                            <div className="absolute bottom-3 left-1/2 -translate-x-1/2 z-10">
                                <EquipmentSlot slot={EQUIPMENT_SLOTS[5]} />
                            </div>
                        </div>

                        {/* Stats Ribbon - pinned at bottom */}
                        <div className="shrink-0 px-6 py-4">
                            <StatsRibbon stats={stats} />
                        </div>
                    </div>
                ) : (
                    <div className="px-6 py-4">
                        <InventoryGrid
                            slots={slots}
                            setSlots={setSlots}
                            onTapItem={handleTapItem}
                            onDoubleTapItem={handleDoubleTapItem}
                        />
                    </div>
                )}
            </div>

            {/* Tab Strip - fixed above bottom nav */}
            <div className="fixed bottom-[80px] w-full z-50 flex gap-1 px-6 bg-[#1a1b21] border-t border-[#554434]/20">
                <button
                    onClick={() => setActiveTab('equipment')}
                    className={`flex-1 py-2.5 text-xs font-bold uppercase tracking-widest transition-colors ${
                        activeTab === 'equipment'
                            ? 'text-[#efc84e] border-b-2 border-[#efc84e]'
                            : 'text-[#a38d7a] border-b-2 border-transparent'
                    }`}
                >
                    Equipment
                </button>
                <button
                    onClick={() => setActiveTab('items')}
                    className={`flex-1 py-2.5 text-xs font-bold uppercase tracking-widest transition-colors ${
                        activeTab === 'items'
                            ? 'text-[#efc84e] border-b-2 border-[#efc84e]'
                            : 'text-[#a38d7a] border-b-2 border-transparent'
                    }`}
                >
                    Items
                </button>
            </div>

            {/* Bottom Navigation */}
            <BottomNav active="inventory" gameId={game.id} />

            {/* Item Info Drawer */}
            <ItemDrawer
                item={drawerItem}
                onClose={() => setDrawerItem(null)}
            />

            {/* Context Menu */}
            {contextMenu && (
                <ContextMenu
                    item={contextMenu.item}
                    position={contextMenu.position}
                    onRemove={handleRemoveItem}
                    onShowMore={(item) => setDrawerItem(item)}
                    onClose={() => setContextMenu(null)}
                />
            )}


        </div>
    );
}
