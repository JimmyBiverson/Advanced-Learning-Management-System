import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import React from 'react';
import { useEditor } from '@/frontend/hooks/use-editor';

const LabelSettings = ({
   changeCustomValues,
}: {
   changeCustomValues: (e: React.ChangeEvent<HTMLInputElement>) => void;
}) => {
   const { editor } = useEditor();

   return (
      editor.editor.selectedElement.type === 'label' && (
         <div className="flex flex-col gap-2">
            <Label>Label Text</Label>
            <Input
               id="innerText"
               placeholder="Enter label text..."
               onChange={changeCustomValues}
               value={
                  typeof editor.editor.selectedElement.content === 'object' &&
                  !Array.isArray(editor.editor.selectedElement.content) &&
                  editor.editor.selectedElement.content !== null &&
                  'innerText' in editor.editor.selectedElement.content
                     ? (editor.editor.selectedElement.content as any)
                          .innerText || ''
                     : ''
               }
            />
         </div>
      )
   );
};

export default LabelSettings;
