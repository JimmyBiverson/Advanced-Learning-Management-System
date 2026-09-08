import { usePage } from '@inertiajs/react';
import type { SortingState } from '@tanstack/react-table';
import {
   flexRender,
   getCoreRowModel,
   getFilteredRowModel,
   getSortedRowModel,
   useReactTable,
} from '@tanstack/react-table';
import { Award, Clock, Lock } from 'lucide-react';
import * as React from 'react';
import TableHeader from '@/components/table/table-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import ExamAttemptColumn from '@/pages/student/partials/exam-attempt-columns';

const ExamAttempts = () => {
   const { exam, attempts, bestAttempt, enrollment } = usePage<StudentExamProps & { enrollment?: ExamEnrollment }>().props;
   const [sorting, setSorting] = React.useState<SortingState>([]);

   const table = useReactTable({
      data: attempts || [],
      columns: ExamAttemptColumn(exam?.id ?? 0, bestAttempt?.id),
      onSortingChange: setSorting,
      getCoreRowModel: getCoreRowModel(),
      getSortedRowModel: getSortedRowModel(),
      getFilteredRowModel: getFilteredRowModel(),
      state: { sorting },
   });

   if (!attempts || attempts.length === 0) {
      return (
         <div className="flex h-full items-center justify-center rounded-lg border border-dashed text-center">
            <div className="space-y-2 p-10">
               <Clock className="mx-auto h-10 w-10 text-muted-foreground" />
               <h2 className="text-xl font-semibold">No attempts yet</h2>
               <p className="text-sm text-muted-foreground">
                  Your exam attempts will appear here once you start the exam.
               </p>
            </div>
         </div>
      );
   }

   return (
      <div className="space-y-4">
         {enrollment?.results_locked && (
            <div className="flex items-center gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900">
               <Lock className="h-5 w-5 flex-shrink-0 text-amber-600" />
               <div className="text-sm">
                  <p className="font-semibold">Results Locked</p>
                  <p className="text-amber-700">Detailed result scores for your exam attempts are currently locked by administration pending fee payment clearance.</p>
               </div>
            </div>
         )}

         {/* Exam Attempts Summary */}
         <div className="grid gap-4 md:grid-cols-4">
            <Card className="p-4">
               <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">
                     Total Attempts
                  </p>
                  <p className="text-2xl font-bold">{attempts.length}</p>
               </div>
            </Card>
            <Card className="p-4">
               <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">Completed</p>
                  <p className="text-2xl font-bold text-green-600">
                     {
                        attempts.filter(
                           (a: ExamAttempt) => a.status === 'completed',
                        ).length
                     }
                  </p>
               </div>
            </Card>
            <Card className="p-4">
               <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">In Progress</p>
                  <p className="text-2xl font-bold text-blue-600">
                     {
                        attempts.filter(
                           (a: ExamAttempt) => a.status === 'in_progress',
                        ).length
                     }
                  </p>
               </div>
            </Card>
            <Card className="p-4">
               <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">Best Score</p>
                  <div className="flex items-center gap-2">
                     <p className="text-2xl font-bold text-purple-600">
                        {bestAttempt?.obtained_marks}
                     </p>
                     {bestAttempt && (
                        <Award className="h-5 w-5 text-amber-500" />
                     )}
                  </div>
               </div>
            </Card>
         </div>

         {/* Attempts Table */}
         <Card>
            <CardHeader className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
               <CardTitle className="text-xl font-semibold">
                  Exam Attempts
               </CardTitle>

               {bestAttempt && (
                  <Badge
                     variant="secondary"
                     className="flex items-center gap-1 bg-amber-100 text-amber-700"
                  >
                     <Award className="h-4 w-4" />
                     Best Score {bestAttempt.obtained_marks}
                  </Badge>
               )}
            </CardHeader>
            <CardContent className="p-0">
               <Table className="border-y border-border">
                  <TableHeader table={table} />

                  <TableBody>
                     {table.getRowModel().rows?.length ? (
                        table.getRowModel().rows.map((row) => (
                           <TableRow
                              key={row.id}
                              data-state={row.getIsSelected() && 'selected'}
                           >
                              {row.getVisibleCells().map((cell) => (
                                 <TableCell key={cell.id}>
                                    {flexRender(
                                       cell.column.columnDef.cell,
                                       cell.getContext(),
                                    )}
                                 </TableCell>
                              ))}
                           </TableRow>
                        ))
                     ) : (
                        <TableRow>
                           <TableCell
                              colSpan={table.getAllColumns().length}
                              className="h-24 text-center"
                           >
                              No exam attempts found.
                           </TableCell>
                        </TableRow>
                     )}
                  </TableBody>
               </Table>
            </CardContent>
         </Card>
      </div>
   );
};

export default ExamAttempts;
