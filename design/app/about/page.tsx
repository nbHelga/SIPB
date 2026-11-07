import Image from "next/image"
import { MainLayout } from "@/components/layout/main-layout"

export default function AboutPage() {
  return (
    <MainLayout>
      {/* Hero Section */}
      <section className="relative h-[50vh] min-h-[400px] overflow-hidden">
        <Image
          src="/mainimage.png"
          alt="About HaloPoala"
          fill
          className="object-cover"
        />
        <div className="absolute inset-0 bg-foreground/40 flex items-center justify-center">
          <h1 className="text-4xl lg:text-6xl font-semibold text-primary-foreground tracking-wider">
            ABOUT US
          </h1>
        </div>
      </section>

      {/* Content */}
      <section className="py-16 lg:py-24 px-4 lg:px-8">
        <div className="container mx-auto max-w-4xl">
          <div className="grid md:grid-cols-2 gap-12 items-center mb-16">
            <div>
              <h2 className="text-2xl lg:text-3xl font-semibold mb-6 tracking-wider">
                OUR STORY
              </h2>
              <p className="text-muted-foreground leading-relaxed mb-4">
                HaloPoala was born from a simple belief: fashion should be accessible, 
                comfortable, and timeless. Founded with a passion for quality craftsmanship 
                and elegant design, we create pieces that make you feel confident and beautiful.
              </p>
              <p className="text-muted-foreground leading-relaxed">
                From our humble beginnings, we have grown into a brand that serves families 
                across Indonesia, offering collections for both adults and children that 
                celebrate individuality and style.
              </p>
            </div>
            <div className="relative aspect-square">
              <Image
                src="https://images.unsplash.com/photo-1558171813-4c088753af8f?w=600&q=80"
                alt="HaloPoala Story"
                fill
                className="object-cover"
              />
            </div>
          </div>

          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div className="relative aspect-square md:order-1">
              <Image
                src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80"
                alt="HaloPoala Values"
                fill
                className="object-cover"
              />
            </div>
            <div className="md:order-2">
              <h2 className="text-2xl lg:text-3xl font-semibold mb-6 tracking-wider">
                OUR VALUES
              </h2>
              <div className="space-y-4">
                <div>
                  <h3 className="font-semibold mb-2">Quality First</h3>
                  <p className="text-muted-foreground text-sm leading-relaxed">
                    We source only the finest materials and work with skilled artisans 
                    to ensure every piece meets our high standards.
                  </p>
                </div>
                <div>
                  <h3 className="font-semibold mb-2">Timeless Design</h3>
                  <p className="text-muted-foreground text-sm leading-relaxed">
                    Our designs transcend trends, offering pieces that remain stylish 
                    season after season.
                  </p>
                </div>
                <div>
                  <h3 className="font-semibold mb-2">Family Focus</h3>
                  <p className="text-muted-foreground text-sm leading-relaxed">
                    We believe in dressing the whole family with the same attention 
                    to detail and style.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </MainLayout>
  )
}
