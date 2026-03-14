export function sumByNumber<T>(
    items: T[],
    resolver: (item: T) => number | null | undefined,
): number {
    return items.reduce(
        (total, item) => total + Number(resolver(item) ?? 0),
        0,
    );
}
