import { router, usePage } from '@inertiajs/react';
import { Check, Globe } from 'lucide-react';
import {
   DropdownMenu,
   DropdownMenuContent,
   DropdownMenuItem,
   DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import change from '@/routes/change';
import { Button } from './ui/button';

const Language = () => {
   const { props } = usePage<SharedData>();
   const { system, direction, langs, locale } = props;

   const directionHandler = () => {
      router.post(change.direction(), {
         direction: direction === 'ltr' ? 'rtl' : 'ltr',
      });
   };

   const langHandler = (lang: string) => {
      router.post(change.lang(), { locale: lang });
   };

   return (
      <DropdownMenu>
         <DropdownMenuTrigger className="cursor-pointer outline-none">
            <Button
               size="icon"
               variant="ghost"
               className="relative h-10 w-10 rounded-full bg-transparent p-0"
            >
               <Globe className="!h-5 !w-5" />
            </Button>
         </DropdownMenuTrigger>

         <DropdownMenuContent align="end" className="w-[160px]">
            {/* {system.fields.direction === 'none' && (
               <>
                  <DropdownMenuItem
                     className="cursor-pointer justify-center px-3 uppercase"
                     onClick={directionHandler}
                  >
                     {direction === 'ltr' ? 'RTL' : 'LTR'}
                  </DropdownMenuItem>

                  <Separator className="my-1" />
               </>
            )} */}

            {langs
               .filter((lang) => lang.is_active)
               .map((lang) => (
                  <DropdownMenuItem
                     key={lang.id}
                     className="cursor-pointer px-3"
                     onClick={() => langHandler(lang.code)}
                  >
                     <span>{lang.name}</span>{' '}
                     {lang.code === locale && <Check />}
                  </DropdownMenuItem>
               ))}
         </DropdownMenuContent>
      </DropdownMenu>
   );
};

export default Language;
