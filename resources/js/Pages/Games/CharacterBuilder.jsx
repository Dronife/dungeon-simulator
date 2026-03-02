import { useState, useEffect } from 'react';
import Layout from '@/Layouts/Layout';

const BASE_PATH = '/images/character_compose/male';

const HAIR_COLORS = [
    { name: 'Red',    hue: 0,   saturate: 100, brightness: 100 },
    { name: 'Brown',  hue: -10, saturate: 60,  brightness: 70  },
    { name: 'Black',  hue: 0,   saturate: 0,   brightness: 30  },
    { name: 'Blonde', hue: 30,  saturate: 100, brightness: 130 },
    { name: 'White',  hue: 0,   saturate: 0,   brightness: 180 },
    { name: 'Blue',   hue: 180, saturate: 100, brightness: 100 },
    { name: 'Green',  hue: 100, saturate: 100, brightness: 90  },
    { name: 'Purple', hue: 240, saturate: 100, brightness: 90  },
];

const SWATCH_COLORS = {
    Red:    '#8B2500',
    Brown:  '#4A2F1A',
    Black:  '#1a1a1a',
    Blonde: '#D4A547',
    White:  '#D9D9D9',
    Blue:   '#2E5B8A',
    Green:  '#2E6B3A',
    Purple: '#5B2E8A',
};

const CATEGORIES = [
    { key: 'bio',    icon: 'fa-id-card',  label: 'Bio' },
    { key: 'sheet',  icon: 'fa-scroll',   label: 'Sheet' },
    { key: 'hair',   icon: 'fa-scissors', label: 'Hair' },
    { key: 'outfit', icon: 'fa-shirt',    label: 'Outfit' },
    { key: 'color',  icon: 'fa-palette',  label: 'Color' },
];

const GENDER_OPTIONS = [
    { key: 'male',   label: 'Male',   icon: 'fa-mars',   available: true },
    { key: 'female', label: 'Female', icon: 'fa-venus',  available: false },
];

const RACE_OPTIONS = [
    { key: 'human',    label: 'Human',    available: true },
    { key: 'elf',      label: 'Elf',      available: false },
    { key: 'dwarf',    label: 'Dwarf',    available: false },
    { key: 'orc',      label: 'Orc',      available: false },
    { key: 'halfling', label: 'Halfling', available: false },
];

const TRAIT_FIELDS = [
    { key: 'personality', icon: 'fa-masks-theater', label: 'Personality' },
    { key: 'traits',      icon: 'fa-fingerprint',   label: 'Traits' },
    { key: 'trauma',      icon: 'fa-ghost',         label: 'Trauma' },
    { key: 'hobbies',     icon: 'fa-gamepad',       label: 'Hobbies' },
    { key: 'routines',    icon: 'fa-clock-rotate-left', label: 'Routines' },
    { key: 'job',         icon: 'fa-briefcase',     label: 'Job' },
    { key: 'skills',      icon: 'fa-screwdriver-wrench', label: 'Skills' },
    { key: 'goals',       icon: 'fa-bullseye',      label: 'Goals' },
    { key: 'secrets',     icon: 'fa-user-secret',   label: 'Secrets' },
    { key: 'limits',      icon: 'fa-ban',           label: 'Limits' },
    { key: 'intentions',  icon: 'fa-compass',       label: 'Intentions' },
];

export default function CharacterBuilder() {
    const [hairFront, setHairFront] = useState(1);
    const [hairBack, setHairBack] = useState(1);
    const [outfit, setOutfit] = useState(1);
    const [hairColorIndex, setHairColorIndex] = useState(0);
    const [activeCategory, setActiveCategory] = useState('bio');
    const [traits, setTraits] = useState({
        name: '', surname: '', age: '', gender: 'male', race: 'human',
        personality: '', traits: '', trauma: '', hobbies: '', routines: '',
        job: '', skills: '', goals: '', secrets: '', limits: '', intentions: '',
    });
    const [generatingField, setGeneratingField] = useState(null);

    // Load saved state on mount
    useEffect(() => {
        const savedAppearance = localStorage.getItem('dnd_character_appearance');
        if (savedAppearance) {
            try {
                const a = JSON.parse(savedAppearance);
                if (a.hairFront) setHairFront(a.hairFront);
                if (a.hairBack) setHairBack(a.hairBack);
                if (a.outfit) setOutfit(a.outfit);
                if (a.hairColorIndex !== undefined) setHairColorIndex(a.hairColorIndex);
                else if (a.hairColor) {
                    const idx = HAIR_COLORS.findIndex(c =>
                        c.hue === a.hairColor.hue && c.saturate === a.hairColor.saturate && c.brightness === a.hairColor.brightness
                    );
                    if (idx >= 0) setHairColorIndex(idx);
                }
            } catch (e) {}
        }

        const savedTraits = localStorage.getItem('dnd_character_traits');
        if (savedTraits) {
            try {
                setTraits(prev => ({ ...prev, ...JSON.parse(savedTraits) }));
            } catch (e) {}
        }
    }, []);

    const hairColor = HAIR_COLORS[hairColorIndex];
    const hairFilter = `hue-rotate(${hairColor.hue}deg) saturate(${hairColor.saturate}%) brightness(${hairColor.brightness}%)`;

    const handleFinish = () => {
        const appearance = {
            hairFront, hairBack, outfit, hairColorIndex,
            hairColor: {
                hue: hairColor.hue,
                saturate: hairColor.saturate,
                brightness: hairColor.brightness,
            },
        };
        localStorage.setItem('dnd_character_appearance', JSON.stringify(appearance));

        const nonEmptyTraits = Object.fromEntries(
            Object.entries(traits).filter(([, v]) => v.trim())
        );
        if (Object.keys(nonEmptyTraits).length > 0) {
            localStorage.setItem('dnd_character_traits', JSON.stringify(nonEmptyTraits));
        }

        window.location.href = '/game';
    };

    const handleTraitChange = (field, value) => {
        setTraits(prev => ({ ...prev, [field]: value }));
    };

    const handleGenerateTrait = async (field) => {
        setGeneratingField(field);
        try {
            const response = await fetch('/api/character/generate-trait', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ field, existing_traits: traits }),
            });
            const data = await response.json();
            if (data.value) {
                setTraits(prev => ({ ...prev, [field]: data.value }));
            }
        } catch (e) {
            console.error('Failed to generate trait:', e);
        } finally {
            setGeneratingField(null);
        }
    };

    return (
        <Layout>
            <div className="h-[calc(100vh-60px)] flex flex-col overflow-hidden bg-zinc-950">
                {/* Character Preview */}
                <div className="flex-1 min-h-0 flex items-center justify-center p-4">
                    <div className="relative h-full aspect-[3/4] max-w-full">
                        <img src={`${BASE_PATH}/hairstyle/back_${hairBack}.png`} className="absolute inset-0 w-full h-full object-contain" style={{ filter: hairFilter }} alt="" />
                        <img src={`${BASE_PATH}/base/normal.png`} className="absolute inset-0 w-full h-full object-contain" alt="" />
                        <img src={`${BASE_PATH}/facial.png`} className="absolute inset-0 w-full h-full object-contain" alt="" />
                        <img src={`${BASE_PATH}/outfit/outfit_${outfit}.png`} className="absolute inset-0 w-full h-full object-contain" alt="" />
                        <img src={`${BASE_PATH}/hairstyle/shadow/front_${hairFront}.png`} className="absolute inset-0 w-full h-full object-contain" alt="" />
                        <img src={`${BASE_PATH}/hairstyle/front_${hairFront}.png`} className="absolute inset-0 w-full h-full object-contain" style={{ filter: hairFilter }} alt="" />
                    </div>
                </div>

                {/* Icon Strip */}
                <div className="shrink-0 flex justify-center gap-2 px-4 py-1 bg-zinc-900 border-t border-zinc-800">
                    {CATEGORIES.map(cat => (
                        <button
                            key={cat.key}
                            onClick={() => setActiveCategory(cat.key)}
                            className={`min-w-[44px] min-h-[44px] flex flex-col items-center justify-center px-3 rounded-lg transition ${
                                activeCategory === cat.key
                                    ? 'text-red-500'
                                    : 'text-zinc-500 hover:text-zinc-300'
                            }`}
                        >
                            <i className={`fa-solid ${cat.icon} text-lg`}></i>
                            <span className="text-[10px] mt-0.5">{cat.label}</span>
                        </button>
                    ))}
                </div>

                {/* Options Panel */}
                <div className="shrink-0 bg-zinc-900 border-t border-zinc-800 rounded-t-2xl overflow-hidden">
                    <div className="p-4 space-y-4 max-h-[40vh] overflow-y-auto">
                        {activeCategory === 'hair' && (
                            <HairPanel
                                hairFront={hairFront} hairBack={hairBack}
                                setHairFront={setHairFront} setHairBack={setHairBack}
                                hairFilter={hairFilter}
                            />
                        )}
                        {activeCategory === 'outfit' && (
                            <OutfitPanel outfit={outfit} setOutfit={setOutfit} />
                        )}
                        {activeCategory === 'color' && (
                            <ColorPanel hairColorIndex={hairColorIndex} setHairColorIndex={setHairColorIndex} />
                        )}
                        {activeCategory === 'bio' && (
                            <BioPanel
                                traits={traits}
                                onChange={handleTraitChange}
                                onGenerate={handleGenerateTrait}
                                generatingField={generatingField}
                            />
                        )}
                        {activeCategory === 'sheet' && (
                            <SheetPanel
                                traits={traits}
                                onChange={handleTraitChange}
                                onGenerate={handleGenerateTrait}
                                generatingField={generatingField}
                            />
                        )}

                        {/* Finish Button */}
                        <button
                            onClick={handleFinish}
                            className="w-full py-3 bg-red-600 hover:bg-red-700 rounded-xl font-semibold transition"
                        >
                            <i className="fa-solid fa-check mr-2"></i>
                            Finished
                        </button>
                    </div>
                </div>
            </div>
        </Layout>
    );
}

function HairPanel({ hairFront, hairBack, setHairFront, setHairBack, hairFilter }) {
    return (
        <div>
            <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-2">
                <i className="fa-solid fa-scissors mr-2"></i>Hair Style
            </h3>
            <div className="grid grid-cols-4 gap-2">
                {[1, 2].map(front =>
                    [1, 2].map(back => {
                        const selected = hairFront === front && hairBack === back;
                        return (
                            <button
                                key={`${front}-${back}`}
                                onClick={() => { setHairFront(front); setHairBack(back); }}
                                className={`aspect-square rounded-lg overflow-hidden border-2 transition relative ${
                                    selected ? 'border-red-500' : 'border-zinc-700 hover:border-zinc-600'
                                }`}
                            >
                                <HairThumbnail front={front} back={back} hairFilter={hairFilter} />
                            </button>
                        );
                    })
                )}
            </div>
        </div>
    );
}

function OutfitPanel({ outfit, setOutfit }) {
    return (
        <div>
            <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-2">
                <i className="fa-solid fa-shirt mr-2"></i>Outfit
            </h3>
            <div className="grid grid-cols-3 gap-2">
                {[1, 2, 3].map(o => {
                    const selected = outfit === o;
                    return (
                        <button
                            key={o}
                            onClick={() => setOutfit(o)}
                            className={`aspect-square rounded-lg overflow-hidden border-2 transition ${
                                selected ? 'border-red-500' : 'border-zinc-700 hover:border-zinc-600'
                            }`}
                        >
                            <img
                                src={`${BASE_PATH}/outfit/outfit_${o}.png`}
                                className="w-full h-full object-contain bg-zinc-800"
                                alt={`Outfit ${o}`}
                            />
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function ColorPanel({ hairColorIndex, setHairColorIndex }) {
    return (
        <div>
            <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-2">
                <i className="fa-solid fa-palette mr-2"></i>Hair Color
            </h3>
            <div className="flex gap-2 flex-wrap">
                {HAIR_COLORS.map((color, i) => {
                    const selected = hairColorIndex === i;
                    return (
                        <button
                            key={color.name}
                            onClick={() => setHairColorIndex(i)}
                            className={`w-11 h-11 rounded-full border-2 transition ${
                                selected ? 'border-red-500 scale-110' : 'border-zinc-600 hover:border-zinc-500'
                            }`}
                            style={{ backgroundColor: SWATCH_COLORS[color.name] }}
                            title={color.name}
                        ></button>
                    );
                })}
            </div>
        </div>
    );
}

function BioPanel({ traits, onChange, onGenerate, generatingField }) {
    return (
        <div className="space-y-4">
            {/* Name + Surname stacked full-width */}
            <div className="space-y-3">
                <div>
                    <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                        <i className="fa-solid fa-signature"></i>Name
                    </label>
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={traits.name || ''}
                            onChange={(e) => onChange('name', e.target.value)}
                            placeholder="First name..."
                            className="flex-1 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2.5 text-sm text-white placeholder-zinc-600 focus:border-red-500 focus:outline-none transition"
                        />
                        <button
                            onClick={() => onGenerate('name')}
                            disabled={generatingField === 'name'}
                            className={`min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg border transition ${
                                generatingField === 'name'
                                    ? 'bg-zinc-700 border-zinc-600 text-zinc-500'
                                    : 'bg-zinc-800 border-zinc-700 text-zinc-400 hover:text-red-500 hover:border-red-500/50'
                            }`}
                        >
                            <i className={`fa-solid ${generatingField === 'name' ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'}`}></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                        <i className="fa-solid fa-signature"></i>Surname
                    </label>
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={traits.surname || ''}
                            onChange={(e) => onChange('surname', e.target.value)}
                            placeholder="Last name..."
                            className="flex-1 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2.5 text-sm text-white placeholder-zinc-600 focus:border-red-500 focus:outline-none transition"
                        />
                        <button
                            onClick={() => onGenerate('surname')}
                            disabled={generatingField === 'surname'}
                            className={`min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg border transition ${
                                generatingField === 'surname'
                                    ? 'bg-zinc-700 border-zinc-600 text-zinc-500'
                                    : 'bg-zinc-800 border-zinc-700 text-zinc-400 hover:text-red-500 hover:border-red-500/50'
                            }`}
                        >
                            <i className={`fa-solid ${generatingField === 'surname' ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'}`}></i>
                        </button>
                    </div>
                </div>
            </div>

            {/* Age + Gender side by side */}
            <div className="flex gap-4">
                <div className="shrink-0">
                    <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                        <i className="fa-solid fa-hourglass-half"></i>Age
                    </label>
                    <input
                        type="number"
                        min="1"
                        max="999"
                        value={traits.age || ''}
                        onChange={(e) => onChange('age', e.target.value)}
                        placeholder="Age"
                        className="w-20 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2.5 text-sm text-white placeholder-zinc-600 focus:border-red-500 focus:outline-none transition"
                    />
                </div>
                <div className="flex-1">
                    <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                        <i className="fa-solid fa-venus-mars"></i>Gender
                    </label>
                    <div className="flex gap-2">
                        {GENDER_OPTIONS.map(opt => (
                            <button
                                key={opt.key}
                                onClick={() => opt.available && onChange('gender', opt.key)}
                                className={`flex-1 h-[44px] rounded-lg border text-sm font-medium transition flex items-center justify-center gap-1.5 ${
                                    !opt.available
                                        ? 'bg-zinc-800/50 border-zinc-700/50 text-zinc-600 cursor-not-allowed'
                                        : traits.gender === opt.key
                                            ? 'bg-red-600/20 border-red-500 text-red-500'
                                            : 'bg-zinc-800 border-zinc-700 text-zinc-400 hover:border-zinc-600'
                                }`}
                            >
                                <i className={`fa-solid ${opt.icon}`}></i>
                                <span>{opt.label}</span>
                                {!opt.available && <span className="text-[9px] text-zinc-600">Soon</span>}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Race */}
            <div>
                <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                    <i className="fa-solid fa-people-group"></i>Race
                </label>
                <div className="grid grid-cols-3 gap-2">
                    {RACE_OPTIONS.map(opt => (
                        <button
                            key={opt.key}
                            onClick={() => opt.available && onChange('race', opt.key)}
                            className={`h-[44px] rounded-lg border text-sm font-medium transition flex items-center justify-center gap-1 ${
                                !opt.available
                                    ? 'bg-zinc-800/50 border-zinc-700/50 text-zinc-600 cursor-not-allowed'
                                    : traits.race === opt.key
                                        ? 'bg-red-600/20 border-red-500 text-red-500'
                                        : 'bg-zinc-800 border-zinc-700 text-zinc-400 hover:border-zinc-600'
                            }`}
                        >
                            {opt.label}
                            {!opt.available && <span className="text-[9px] text-zinc-600">Soon</span>}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

function SheetPanel({ traits, onChange, onGenerate, generatingField }) {
    return (
        <div className="space-y-3">
            {TRAIT_FIELDS.map(field => (
                <div key={field.key}>
                    <label className="text-zinc-400 text-xs uppercase tracking-wide flex items-center gap-1.5 mb-1">
                        <i className={`fa-solid ${field.icon}`}></i>
                        {field.label}
                    </label>
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={traits[field.key] || ''}
                            onChange={(e) => onChange(field.key, e.target.value)}
                            placeholder={`Enter ${field.label.toLowerCase()}...`}
                            className="flex-1 bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white placeholder-zinc-600 focus:border-red-500 focus:outline-none transition"
                        />
                        <button
                            onClick={() => onGenerate(field.key)}
                            disabled={generatingField === field.key}
                            className={`min-w-[44px] min-h-[44px] flex items-center justify-center rounded-lg border transition ${
                                generatingField === field.key
                                    ? 'bg-zinc-700 border-zinc-600 text-zinc-500'
                                    : 'bg-zinc-800 border-zinc-700 text-zinc-400 hover:text-red-500 hover:border-red-500/50'
                            }`}
                        >
                            <i className={`fa-solid ${generatingField === field.key ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'}`}></i>
                        </button>
                    </div>
                </div>
            ))}
        </div>
    );
}

function HairThumbnail({ front, back, hairFilter }) {
    return (
        <div className="relative w-full h-full bg-zinc-800">
            <img
                src={`${BASE_PATH}/hairstyle/back_${back}.png`}
                className="absolute inset-0 w-full h-full object-contain"
                style={{ filter: hairFilter }}
                alt=""
            />
            <img
                src={`${BASE_PATH}/base/normal.png`}
                className="absolute inset-0 w-full h-full object-contain"
                alt=""
            />
            <img
                src={`${BASE_PATH}/hairstyle/front_${front}.png`}
                className="absolute inset-0 w-full h-full object-contain"
                style={{ filter: hairFilter }}
                alt=""
            />
        </div>
    );
}
