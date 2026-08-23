import DeleteModal from '@/components/inertia/delete-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { destroy as destroyExamEnrollment } from '@/routes/exam-enrollments';
import { destroy as destroyCourseEnrollment } from '@/routes/exam-enrollments';
import { router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Lock, ShieldCheck, Trash2, Unlock } from 'lucide-react';

const GovernanceActions = ({ enrollment }: { enrollment: ExamEnrollment }) => {
   const updateGovernance = (changes: Partial<ExamEnrollment>) => {
      router.patch(
         `/dashboard/exams/exam/enrollments/${enrollment.id}/governance`,
         {
            access_granted: changes.access_granted ?? enrollment.access_granted,
            payment_status: changes.payment_status ?? enrollment.payment_status,
            amount_paid: changes.amount_paid ?? enrollment.amount_paid,
            results_locked: changes.results_locked ?? enrollment.results_locked,
         },
         { preserveScroll: true },
      );
   };

   return (
      <div className="flex items-center gap-1">
         <Button
            type="button"
            size="sm"
            variant="outline"
            title={enrollment.access_granted ? 'Revoke exam access' : 'Grant exam access'}
            onClick={() => updateGovernance({ access_granted: !enrollment.access_granted })}
         >
            {enrollment.access_granted ? <ShieldCheck /> : <Unlock />}
         </Button>
         <Button
            type="button"
            size="sm"
            variant="outline"
            title={enrollment.results_locked ? 'Unlock results' : 'Lock results'}
            onClick={() => updateGovernance({ results_locked: !enrollment.results_locked })}
         >
            {enrollment.results_locked ? <Lock /> : <Unlock />}
         </Button>
      </div>
   );
};

const AdminTableColumn = (
   enrollmentType: 'course' | 'exam',
   translate: LanguageTranslations,
   deleteRoute: string,
): ColumnDef<CourseEnrollment | ExamEnrollment>[] => {
   const { table } = translate;

   return [
      {
         id: 'index',
         header: () => <div className="pl-4">#</div>,
         cell: ({ row }) => (
            <div className="w-4 pl-4 text-center font-medium">
               {row.index + 1}
            </div>
         ),
      },
      {
         id: 'name',
         header: table.name,
         cell: ({ row }) => {
            const user = row.original.user;

            return (
               <div className="flex items-center gap-3">
                  <div className="h-12 w-12 overflow-hidden rounded-full bg-muted">
                     {user.photo ? (
                        <img
                           src={user.photo}
                           alt={user.name}
                           className="h-full w-full object-cover"
                        />
                     ) : (
                        <div className="flex h-full w-full items-center justify-center bg-gray-200 text-gray-500">
                           <span className="text-lg">
                              {table.img_placeholder}
                           </span>
                        </div>
                     )}
                  </div>
                  <div>
                     <p className="font-medium">{user.name}</p>
                     <p className="text-sm text-muted-foreground">
                        {user.email}
                     </p>
                  </div>
               </div>
            );
         },
      },
      {
         id: 'enrolled_course',
         header: () =>
            enrollmentType === 'course'
               ? table.enrolled_course
               : 'Enrolled Exam',
         cell: ({ row }) => {
            const exam = row.original as ExamEnrollment;
            const course = row.original as CourseEnrollment;

            return (
               <div className="max-w-md">
                  <p className="line-clamp-1">
                     {enrollmentType === 'course'
                        ? course.course.title
                        : exam.exam.title}
                  </p>
               </div>
            );
         },
      },
      {
         id: 'enrolled_date',
         header: table.enrolled_date,
         cell: ({ row }) => {
            // Convert to a readable date format
            const date = new Date(row.original.entry_date);
            const formattedDate = date.toLocaleDateString('en-US', {
               month: 'long',
               day: '2-digit',
               year: 'numeric',
            });

            return <div>{formattedDate}</div>;
         },
      },
      {
         id: 'expiry_date',
         header: table.expiry_date,
         cell: ({ row }) => {
            if (!row.original.expiry_date) {
               return (
                  <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                     {table.lifetime_access}
                  </Badge>
               );
            }

            const date = new Date(row.original.expiry_date);
            const formattedDate = date.toLocaleDateString('en-US', {
               month: 'long',
               day: '2-digit',
               year: 'numeric',
            });

            return <div>{formattedDate}</div>;
         },
      },
      {
         id: 'governance',
         header: 'Controls',
         cell: ({ row }) =>
            enrollmentType === 'exam' ? (
               <GovernanceActions enrollment={row.original as ExamEnrollment} />
            ) : null,
      },
      {
         id: 'actions',
         header: () => <div className="pr-4 text-end">{table.action}</div>,
         cell: ({ row }) => {
            return (
               <div className="flex justify-end pr-4">
                  <DeleteModal
                     routePath={
                        deleteRoute === 'exam-enrollments.destroy'
                           ? destroyExamEnrollment.url(row.original.id)
                           : destroyCourseEnrollment.url(row.original.id)
                     }
                     actionComponent={
                        <Button
                           size="icon"
                           variant="ghost"
                           className="rounded-full text-destructive hover:bg-destructive/10 hover:text-destructive"
                        >
                           <Trash2 />
                        </Button>
                     }
                  />
               </div>
            );
         },
      },
   ];
};

export default AdminTableColumn;
