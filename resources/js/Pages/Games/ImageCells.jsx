/**
 * Display a specific cell from a 2x2 grid image
 * 1=top-left, 2=top-right, 3=bottom-left, 4=bottom-right
 */
export function Grid2x2Cell({ imagePath, cell, className = '', cover = false }) {
    const positions = {
        1: '0% 0%',
        2: '100% 0%',
        3: '0% 100%',
        4: '100% 100%',
    };

    return (
        <div
            className={`bg-[#1e1f25] ${className}`}
            style={{
                backgroundImage: `url(/storage/${imagePath})`,
                backgroundSize: cover ? '250% 250%' : '200% 200%',
                backgroundPosition: positions[cell] || '0% 0%',
            }}
        />
    );
}

/**
 * Display a specific cell from the character matrix image
 * Grid layout:
 * Row 0: 1, 2, ZXC(2x2)
 * Row 1: 3, 4, ZXC
 * Row 2: 5, 6, 7, 8
 * Row 3: 9, 10, 11, 12
 */
export function MatrixCell({ imagePath, cell, className = '', cover = false }) {
    const cellPositions = {
        1: { col: 0, row: 0 },
        2: { col: 1, row: 0 },
        3: { col: 0, row: 1 },
        4: { col: 1, row: 1 },
        5: { col: 0, row: 2 },
        6: { col: 1, row: 2 },
        7: { col: 2, row: 2 },
        8: { col: 3, row: 2 },
        9: { col: 0, row: 3 },
        10: { col: 1, row: 3 },
        11: { col: 2, row: 3 },
        12: { col: 3, row: 3 },
        'zxc': { col: 2, row: 0, span: 2 },
    };

    const pos = cellPositions[cell];
    if (!pos) return null;

    const isZxc = cell === 'zxc';
    const bgSize = cover
        ? (isZxc ? '250% 250%' : '500% 500%')
        : (isZxc ? '200% 200%' : '400% 400%');
    const xPos = isZxc ? '100%' : `${pos.col * (100/3)}%`;
    const yPos = isZxc ? '0%' : `${pos.row * (100/3)}%`;

    return (
        <div
            className={`bg-[#1e1f25] object-fill ${className}`}
            style={{
                backgroundImage: `url(/storage/${imagePath})`,
                backgroundSize: bgSize,
                backgroundPosition: `${xPos} ${yPos}`,
            }}
        />
    );
}
