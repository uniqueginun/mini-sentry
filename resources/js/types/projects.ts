export type ProjectKey = {
    id: number;
    prefix: string;
    is_active: boolean;
    created_at: string | null;
};

export type Project = {
    id: number;
    name: string;
    created_at: string | null;
    keys: ProjectKey[];
};

export type GeneratedKey = {
    projectId: number;
    publicKey: string;
};
