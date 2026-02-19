import { useState } from 'react';
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

// Preview swatches for the color chips
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

export default function CharacterBuilder() {
    const [hairFront, setHairFront] = useState(1);
    const [hairBack, setHairBack] = useState(1);
    const [outfit, setOutfit] = useState(1);
    const [hairColorIndex, setHairColorIndex] = useState(0);

    const hairColor = HAIR_COLORS[hairColorIndex];

    const handleFinish = () => {
        const appearance = {
            hairFront,
            hairBack,
            outfit,
            hairColor: {
                hue: hairColor.hue,
                saturate: hairColor.saturate,
                brightness: hairColor.brightness,
            },
        };
        localStorage.setItem('dnd_character_appearance', JSON.stringify(appearance));
        window.location.href = '/game';
    };

    const hairFilter = `hue-rotate(${hairColor.hue}deg) saturate(${hairColor.saturate}%) brightness(${hairColor.brightness}%)`;

    return (
        <Layout>
            <div className="h-[calc(100vh-60px)] flex flex-col overflow-hidden bg-zinc-950">
                {/* Character Preview — top half */}
                <div className="flex-1 min-h-0 flex items-center justify-center p-4">
                    <div className="relative h-full aspect-[3/4] max-w-full">
                        {/* Layer 1: Back hair */}
                        <img
                            src={`${BASE_PATH}/hairstyle/back_${hairBack}.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            style={{ filter: hairFilter }}
                            alt=""
                        />
                        {/* Layer 2: Base body */}
                        <img
                            src={`${BASE_PATH}/base/normal.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            alt=""
                        />
                        {/* Layer 3: Facial features */}
                        <img
                            src={`${BASE_PATH}/facial.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            alt=""
                        />
                        {/* Layer 4: Outfit */}
                        <img
                            src={`${BASE_PATH}/outfit/outfit_${outfit}.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            alt=""
                        />
                        {/* Layer 5: Hair shadow */}
                        <img
                            src={`${BASE_PATH}/hairstyle/shadow/front_${hairFront}.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            alt=""
                        />
                        {/* Layer 6: Front hair */}
                        <img
                            src={`${BASE_PATH}/hairstyle/front_${hairFront}.png`}
                            className="absolute inset-0 w-full h-full object-contain"
                            style={{ filter: hairFilter }}
                            alt=""
                        />
                    </div>
                </div>

                {/* Controls — bottom half */}
                <div className="shrink-0 bg-zinc-900 border-t border-zinc-800 rounded-t-2xl p-4 space-y-4">
                    {/* Hair Style */}
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

                    {/* Outfit */}
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

                    {/* Hair Color */}
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
                                        className={`w-8 h-8 rounded-full border-2 transition ${
                                            selected ? 'border-red-500 scale-110' : 'border-zinc-600 hover:border-zinc-500'
                                        }`}
                                        style={{ backgroundColor: SWATCH_COLORS[color.name] }}
                                        title={color.name}
                                    ></button>
                                );
                            })}
                        </div>
                    </div>

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
        </Layout>
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
