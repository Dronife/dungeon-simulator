import { MatrixCell } from './ImageCells';

export default function CharacterDrawerContent({ character }) {
    const stats = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
    const calcMod = (val) => {
        const mod = Math.floor((val - 10) / 2);
        return mod >= 0 ? `+${mod}` : mod;
    };

    return (
        <div className="space-y-6">
            {/* Portrait + Name */}
            <div className="bg-[#292a2f] rounded-lg overflow-hidden">
                <div className="aspect-square relative overflow-hidden">
                    {character.image_path ? (
                        <MatrixCell
                            imagePath={character.image_path}
                            cell={1}
                            className="w-full h-full"
                        />
                    ) : (
                        <div className="w-full h-full bg-[#1e1f25] flex items-center justify-center">
                            <i className="fa-solid fa-user text-6xl text-[#554434]/30"></i>
                        </div>
                    )}
                    <div className="absolute bottom-0 inset-x-0 bg-gradient-to-t from-[#1a1b21] to-transparent p-4">
                        <h2 className="text-2xl font-narration text-[#efc84e]">{character.name}</h2>
                        <p className="text-[#a38d7a]">{character.job || 'Adventurer'}</p>
                    </div>
                </div>

                <div className="p-4">
                    <p className="text-[#dbc2ad] text-sm">{character.info}</p>
                </div>
            </div>

            {/* Equipment Slots */}
            <div>
                <h3 className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-3">
                    <i className="fa-solid fa-suitcase mr-2"></i>Equipment
                </h3>
                <div className="grid grid-cols-3 gap-3">
                    {[0, 1, 2].map(i => (
                        <div key={i} className="aspect-square bg-[#292a2f] border border-[#554434]/30 border-dashed rounded-lg flex items-center justify-center">
                            <i className="fa-solid fa-plus text-[#554434]/40"></i>
                        </div>
                    ))}
                </div>
            </div>

            {/* Stats Grid */}
            <div>
                <h3 className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-3">
                    <i className="fa-solid fa-chart-simple mr-2"></i>Attributes
                </h3>
                <div className="grid grid-cols-6 gap-2 text-center">
                    {stats.map(stat => (
                        <div key={stat} className="bg-[#292a2f] rounded p-2">
                            <div className="text-[10px] text-[#a38d7a] uppercase">{stat}</div>
                            <div className="font-bold text-[#e3e1e9]">{character[stat] || 10}</div>
                            <div className="text-xs text-[#efc84e]">{calcMod(character[stat] || 10)}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* HP & Temps */}
            <div className="flex justify-between text-sm bg-[#292a2f] rounded-lg p-3">
                <div>
                    <i className="fa-solid fa-heart text-[#ffb4a8] mr-1"></i>
                    <span className="font-bold text-[#e3e1e9]">{character.hp}/{character.max_hp}</span>
                </div>
                <div className="flex gap-4">
                    <span className="text-[#a38d7a]">
                        <i className="fa-solid fa-shuffle mr-1"></i>
                        <span className="text-[#e3e1e9]">{character.chaotic_temperature}</span>
                    </span>
                    <span className="text-[#a38d7a]">
                        <i className="fa-solid fa-sun mr-1"></i>
                        <span className="text-[#e3e1e9]">{character.positive_temperature}</span>
                    </span>
                </div>
            </div>

            {/* Character Traits */}
            <div className="space-y-3">
                {character.personality && <Attr icon="fa-masks-theater" label="Personality" value={character.personality} />}
                {character.traits && <Attr icon="fa-fingerprint" label="Traits" value={character.traits} />}
                {character.trauma && <Attr icon="fa-ghost" label="Trauma" value={character.trauma} />}
                {character.hobbies && <Attr icon="fa-gamepad" label="Hobbies" value={character.hobbies} />}
                {character.routines && <Attr icon="fa-clock-rotate-left" label="Routines" value={character.routines} />}
                {character.skills && <Attr icon="fa-screwdriver-wrench" label="Skills" value={character.skills} />}
                {character.goals && <Attr icon="fa-bullseye" label="Goals" value={character.goals} />}
                {character.intentions && <Attr icon="fa-compass" label="Intentions" value={character.intentions} />}
                {character.secrets && <Attr icon="fa-user-secret" label="Secrets" value={character.secrets} />}
                {character.limits && <Attr icon="fa-ban" label="Limits" value={character.limits} />}
            </div>
        </div>
    );
}

function Attr({ icon, label, value }) {
    return (
        <div className="bg-[#292a2f] rounded-lg p-3">
            <div className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-1">
                <i className={`fa-solid ${icon} mr-2`}></i>
                {label}
            </div>
            <p className="text-[#dbc2ad] text-sm">{value}</p>
        </div>
    );
}
