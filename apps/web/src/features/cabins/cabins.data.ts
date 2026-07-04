export type Cabin = {
  id: string;
  name: string;
  description: string;
  maxGuests: number;
  bedrooms: number;
  bathrooms: number;
  pricePerNight: number;
};

export const cabins: Cabin[] = [
  {
    id: "sosnowy",
    name: "Domek Sosnowy",
    description: "Przytulny domek wśród lasów",
    maxGuests: 6,
    bedrooms: 2,
    bathrooms: 1,
    pricePerNight: 450,
  },
  {
    id: "lesny",
    name: "Domek Leśny",
    description: "Idealny na spokojny wypoczynek",
    maxGuests: 4,
    bedrooms: 1,
    bathrooms: 1,
    pricePerNight: 380,
  },
];