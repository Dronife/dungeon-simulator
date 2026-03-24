export default function ConceptArtPreview({ appearance, className = '' }) {
    const basePath = '/images/character_compose/male/concept_art';
    const artFile = `outfit_${appearance.outfit}_front_${appearance.hairFront}_back_${appearance.hairBack}`;
    const hairFilter = appearance.hairColor
        ? `hue-rotate(${appearance.hairColor.hue}deg) saturate(${appearance.hairColor.saturate}%) brightness(${appearance.hairColor.brightness}%)`
        : 'none';

    return (
        <>
            <div
                className={`bg-[#1e1f25] ${className}`}
                style={{
                    backgroundImage: `url(${basePath}/${artFile}.png)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                }}
            />
            <div
                className={className}
                style={{
                    backgroundImage: `url(${basePath}/hair_cutout/${artFile}_hair.png)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    filter: hairFilter,
                }}
            />
        </>
    );
}
