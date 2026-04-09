export interface Workspace {
    id: number;
    name: string;
    description?: string;
    role: 'owner' | 'member';
}
