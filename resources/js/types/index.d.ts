export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Agent {
    id: number;
    name: string;
    slug: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        agents: Agent[];
    };
};
