import { Link, usePage } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
   Sidebar,
   SidebarContent,
   SidebarHeader,
   SidebarMenu,
   SidebarMenuItem,
   useSidebar,
} from '@/components/ui/sidebar';
import { NavMain } from '@/layouts/dashboard/partials/nav-main';
import { cn } from '@/lib/utils';

const DashboardSidebar = () => {
   const { state } = useSidebar();
   const { props } = usePage<SharedData>();
   const compact = state === 'collapsed';

   return (
      <Sidebar
         collapsible="icon"
         variant="inset"
         side={props.direction === 'rtl' ? 'right' : 'left'}
         className="z-50 border-r border-border bg-transparent p-0 shadow-none"
      >
         <ScrollArea className={cn('h-full', compact ? 'p-0' : 'p-2')}>
            {!compact && (
               <SidebarHeader>
                  <SidebarMenu>
                     <SidebarMenuItem className="py-3">
                        <Link
                           href="/"
                           prefetch
                           className={`flex items-center gap-2 overflow-hidden ${compact ? 'justify-center' : ''}`}
                        >
                           {/* {compact ? (
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-lg font-bold text-primary-foreground shadow-sm transition-all duration-200 hover:scale-105">
                           {props.system.name.charAt(0).toUpperCase()}
                        </div>
                     ) : (
                        <AppLogo className="h-[26px]" />
                     )} */}
                           <AppLogo className="h-[26px]" />
                        </Link>
                     </SidebarMenuItem>
                  </SidebarMenu>
               </SidebarHeader>
            )}

            <SidebarContent>
               <NavMain />
            </SidebarContent>
         </ScrollArea>
      </Sidebar>
   );
};

export default DashboardSidebar;
