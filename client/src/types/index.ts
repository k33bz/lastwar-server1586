// Alliance types
export interface Alliance {
  tag: string;
  name: string;
  r5: {
    name: string;
    gameId: string | null;
    discordId: string | null;
  };
  signed: boolean;
  power: number;
  discord?: {
    serverName: string;
    inviteUrl: string | null;
    logoUrl: string;
    memberCount: number | null;
  };
  crossServer?: {
    hasPartner: boolean;
    servers: number[];
    partnerTags?: string[];
    partnerTag?: string;
    network?: string | null;
  };
  info?: {
    description: string;
    founded: string | null;
    languages: string[];
    timezone: string;
    recruiting: boolean;
    requirements?: {
      minPower: number;
      minLevel: number;
      activity: string;
      notes: string;
    };
  };
  contact?: {
    recruitmentContact: string | null;
    discordRecruitment: string | null;
  };
  achievements?: {
    rankHistory: any[];
    peakPower: number;
    peakRank: number;
    specialties: string[];
  };
  metadata?: {
    lastUpdated: string;
    verified: boolean;
    featured: boolean;
  };
  r5History?: Array<{
    r5Name: string;
    gameId: string | null;
    discordId: string | null;
    startDate: string;
    endDate: string | null;
    current: boolean;
    signatures: any[];
  }>;
}

// Server info types
export interface ServerInfo {
  serverId: number;
  name: string;
  region: string;
  openDate: string;
  discord: {
    serverName: string;
    inviteUrl: string;
    logoUrl: string;
    description: string;
    memberCount: number | null;
    features: string[];
  };
  nap15: {
    active: boolean;
    startDate: string;
    memberCount: number;
    version: string;
  };
  council: {
    permanentSeats: number;
    rotatingSeats: number;
    rotationFrequency: string;
    rotationDay: string;
    rotationTime: string;
  };
  contact: {
    admin: string;
    discordAdmin: string;
    website: string;
  };
  metadata: {
    lastUpdated: string;
    version: string;
  };
}

// Rules types
export interface Rule {
  number: string;
  text: string;
  subsections?: {
    number: string;
    text: string;
  }[];
}

export interface RulesData {
  version: string;
  effectiveDate: string;
  sections: {
    title: string;
    rules: Rule[];
  }[];
}

// Amendment types
export interface Amendment {
  id: number;
  date: string;
  version: string;
  changes: {
    type: string;
    rule: string;
    description: string;
  }[];
}

// Council types
export interface CouncilMember {
  tag: string;
  name: string;
  seat: string;
  startDate?: string;
}
